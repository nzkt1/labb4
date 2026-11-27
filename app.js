// Frontend JavaScript for Lab Work 4.
// Talks to the PHP backend with fetch(). Each part only runs on the page that has its elements.

const API = "../backend";

// Fetch a URL and parse the JSON answer (also when the server replies with an error status).
async function requestJson(url, options) {
  const response = await fetch(url, options);
  let data;
  try {
    data = await response.json();
  } catch (error) {
    throw new Error("The server did not return valid JSON.");
  }
  return { ok: response.ok, data: data };
}

// Show the logo box only if the image file exists.
document.querySelectorAll(".logo").forEach((img) => {
  img.addEventListener("error", () => { img.style.display = "none"; });
});

// ---------- Home page: check that the backend is reachable ----------
const statusBox = document.getElementById("server-status");

if (statusBox) {
  const statusText = document.getElementById("status-text");
  const recheck = document.getElementById("recheck");

  function setStatus(state, text) {
    statusBox.dataset.state = state;
    statusText.textContent = text;
  }

  async function checkBackend() {
    setStatus("checking", "Checking backend…");
    try {
      const result = await requestJson(API + "/get_students.php", { cache: "no-store" });
      if (!result.ok || typeof result.data.count !== "number") {
        throw new Error("Unexpected response");
      }
      const n = result.data.count;
      setStatus("online", "Backend connected ✔ (" + n + " student" + (n === 1 ? "" : "s") + " saved)");
    } catch (error) {
      setStatus("offline", "Backend not reachable – start it with: php -S localhost:8000");
    }
  }

  recheck.addEventListener("click", checkBackend);
  checkBackend();
}

// ---------- Form page: send the form to submit.php ----------
const studentForm = document.getElementById("studentForm");

if (studentForm) {
  const msg = document.getElementById("msg");
  const submitBtn = document.getElementById("submitBtn");

  function clearMessage() {
    msg.hidden = true;
    msg.className = "alert";
    msg.textContent = "";
  }

  function showSuccess(text) {
    msg.className = "alert alert-success";
    msg.textContent = "";
    const strong = document.createElement("strong");
    strong.textContent = text;
    const link = document.createElement("a");
    link.href = "table.html";
    link.textContent = "View Table →";
    msg.append(strong, link);
    msg.hidden = false;
  }

  function showErrors(errors) {
    msg.className = "alert alert-error";
    msg.textContent = "";
    const title = document.createElement("strong");
    title.textContent = errors.length > 1 ? "Please fix the following:" : "Please fix this:";
    const list = document.createElement("ul");
    errors.forEach((text) => {
      const li = document.createElement("li");
      li.textContent = text;
      list.appendChild(li);
    });
    msg.append(title, list);
    msg.hidden = false;
  }

  studentForm.addEventListener("submit", async (event) => {
    event.preventDefault(); // stay on the page
    clearMessage();
    submitBtn.disabled = true;
    submitBtn.textContent = "Saving…";

    try {
      const result = await requestJson(API + "/submit.php", {
        method: "POST",
        body: new FormData(studentForm),
      });
      const data = result.data;

      if (data.success) {
        showSuccess(data.message);
        studentForm.reset();
      } else {
        showErrors(Array.isArray(data.errors) && data.errors.length ? data.errors : ["Something went wrong."]);
      }
    } catch (error) {
      showErrors(["Could not reach the backend. Start the PHP server with: php -S localhost:8000"]);
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = "Submit";
    }
  });

  studentForm.addEventListener("reset", clearMessage);
}

// ---------- Table page: load students from get_students.php ----------
const tableBody = document.getElementById("tableBody");

if (tableBody) {
  const averageCell = document.getElementById("average");
  const averageCard = document.getElementById("averageCard");
  const countCard = document.getElementById("count");
  const refreshBtn = document.getElementById("refreshBtn");

  // Maps a grade to its CSS class (the "+" is not allowed in class names).
  const GRADE_CLASS = { "A+": "grade-Aplus", "A": "grade-A", "B": "grade-B", "C": "grade-C", "D": "grade-D" };

  // One full-width row used for the loading, empty and error messages.
  function showStateRow(kind, text, withRetry) {
    tableBody.textContent = "";
    const tr = document.createElement("tr");
    tr.className = "state-row" + (kind === "error" ? " is-error" : "");
    const td = document.createElement("td");
    td.colSpan = 7;

    if (kind === "loading") {
      const spinner = document.createElement("span");
      spinner.className = "spinner";
      td.appendChild(spinner);
    }
    td.appendChild(document.createTextNode(text));

    if (withRetry) {
      td.appendChild(document.createElement("br"));
      const retry = document.createElement("button");
      retry.type = "button";
      retry.className = "btn btn-secondary";
      retry.textContent = "Try again";
      retry.addEventListener("click", loadStudents);
      td.appendChild(retry);
    }

    tr.appendChild(td);
    tableBody.appendChild(tr);
  }

  function setAverage(text) {
    averageCell.textContent = text;
    averageCard.textContent = text;
  }

  async function loadStudents() {
    showStateRow("loading", " Loading students…", false);
    countCard.textContent = "–";
    setAverage("–");

    try {
      const result = await requestJson(API + "/get_students.php", { cache: "no-store" });
      const data = result.data;
      if (!result.ok || !Array.isArray(data.students)) {
        throw new Error("Unexpected response");
      }

      countCard.textContent = data.students.length;

      if (data.students.length === 0) {
        showStateRow("empty", "No students yet. Add one using the Form page.", false);
        setAverage("0");
        return;
      }

      // The backend has already escaped the text with htmlspecialchars(),
      // so it is safe to build the rows as HTML here.
      tableBody.innerHTML = data.students.map((s, i) => `
        <tr>
          <td>${i + 1}</td>
          <td>${s.enrollment}</td>
          <td>${s.name}</td>
          <td>${s.course}</td>
          <td>${s.gender}</td>
          <td class="num">${Number(s.marks)}</td>
          <td><span class="grade ${GRADE_CLASS[s.grade] || ""}">${s.grade}</span></td>
        </tr>`).join("");

      setAverage(Number(data.average).toFixed(1));
    } catch (error) {
      countCard.textContent = "–";
      setAverage("–");
      showStateRow("error", "Could not load data. Make sure the PHP server is running (php -S localhost:8000).", true);
    }
  }

  refreshBtn.addEventListener("click", loadStudents);
  loadStudents();
}
