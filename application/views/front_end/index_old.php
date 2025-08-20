<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Task Manager</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
  <div class="container">
    <h2 class="mb-4">Task Manager</h2>

    <!-- Task Form -->
    <form id="taskForm" class="row g-2 mb-4">
      <input type="hidden" id="taskId">
      <div class="col-md-3">
        <input type="text" id="title" class="form-control" placeholder="Task Title" required>
      </div>
      <div class="col-md-2">
        <select id="priority" class="form-control">
          <option value="medium">Medium</option>
          <option value="low">Low</option>
          <option value="high">High</option>
        </select>
      </div>
      <div class="col-md-2">
        <select id="status" class="form-control">
          <option value="pending">Pending</option>
          <option value="completed">Completed</option>
        </select>
      </div>
      <div class="col-md-3">
        <input type="date" id="due_date" class="form-control">
      </div>
      <div class="col-md-2">
        <button type="submit" id="submitBtn" class="btn btn-primary w-100">Create</button>
      </div>
    </form>

    <!-- Tasks Table -->
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>Title</th>
          <th>Status</th>
          <th>Priority</th>
          <th>Due Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="taskTableBody"></tbody>
    </table>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script>
    const API_URL = "http://localhost/ci3_task_manager/index.php/tasks"; 
    const taskTableBody = document.getElementById("taskTableBody");
    const taskForm = document.getElementById("taskForm");
    const submitBtn = document.getElementById("submitBtn");

    let editing = false;

    // Load tasks
    function loadTasks() {
      axios.get(API_URL).then(res => {
        taskTableBody.innerHTML = "";
        res.data.forEach(task => {
          taskTableBody.innerHTML += `
            <tr>
              <td>${task.title}</td>
              <td>${task.status}</td>
              <td>${task.priority}</td>
              <td>${task.due_date ?? ""}</td>
              <td>
                <button class="btn btn-sm btn-warning me-1" onclick="editTask(${task.id}, '${task.title}', '${task.status}', '${task.priority}', '${task.due_date}')">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteTask(${task.id})">Delete</button>
              </td>
            </tr>`;
        });
      }).catch(err => {
        console.error("Error loading tasks:", err);
      });
    }

    // Create / Update task
    taskForm.addEventListener("submit", function(e) {
      e.preventDefault();

      const id = document.getElementById("taskId").value;
      const taskData = {
        title: document.getElementById("title").value,
        status: document.getElementById("status").value,
        priority: document.getElementById("priority").value,
        due_date: document.getElementById("due_date").value
      };

      if (editing) {
        // Update
        axios.put(`${API_URL}/${id}`, taskData).then(() => {
          resetForm();
          loadTasks();
        }).catch(err => {
          console.error("Error updating task:", err);
        });
      } else {
        // Create
        axios.post(API_URL, taskData).then(() => {
          resetForm();
          loadTasks();
        }).catch(err => {
          console.error("Error creating task:", err);
        });
      }
    });

    // Edit task (fill form)
    function editTask(id, title, status, priority, due_date) {
      document.getElementById("taskId").value = id;
      document.getElementById("title").value = title;
      document.getElementById("status").value = status;
      document.getElementById("priority").value = priority;
      document.getElementById("due_date").value = due_date;

      editing = true;
      submitBtn.textContent = "Update";
      submitBtn.classList.remove("btn-primary");
      submitBtn.classList.add("btn-success");
    }

    // Delete task
    function deleteTask(id) {
      if (confirm("Are you sure you want to delete this task?")) {
        axios.delete(`${API_URL}/${id}`).then(() => {
          loadTasks();
        }).catch(err => {
          console.error("Error deleting task:", err);
        });
      }
    }

    // Reset form
    function resetForm() {
      taskForm.reset();
      document.getElementById("taskId").value = "";
      editing = false;
      submitBtn.textContent = "Create";
      submitBtn.classList.remove("btn-success");
      submitBtn.classList.add("btn-primary");
    }

    // Initial load
    loadTasks();
  </script>
</body>
</html>
