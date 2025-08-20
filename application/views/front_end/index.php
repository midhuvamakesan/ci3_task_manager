<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Task Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">Task Manager</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#taskModal">+ Add Task</button>
  </div>

  <!-- Filter Section -->
  <div class="card shadow-sm p-3 mb-4">
    <form id="filterForm" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Search Title</label>
        <input type="text" id="search" class="form-control" placeholder="Enter keyword">
      </div>
      <div class="col-md-2">
        <label class="form-label">Status</label>
        <select id="statusFilter" class="form-select">
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Priority</label>
        <select id="priorityFilter" class="form-select">
          <option value="">All</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Due From</label>
        <input type="date" id="dueFrom" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">Due To</label>
        <input type="date" id="dueTo" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Tag</label>
        <select id="tagFilter" class="form-select">
          <option value="">All</option>
        </select>
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn btn-success w-100">Go</button>
      </div>
    </form>
  </div>

  <!-- Task Table -->
  <div class="card shadow-sm">
    <div class="card-body">
      <table class="table table-hover align-middle" id="taskTable">
        <thead class="table-light">
          <tr>
            <th>Title</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Due Date</th>
            <th>Tags</th>
            <th style="width: 220px;">Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>

      <!-- Pagination -->
      <nav>
        <ul class="pagination justify-content-center" id="pagination"></ul>
      </nav>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="taskModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="taskForm">
        <div class="modal-header">
          <h5 class="modal-title">Add / Edit Task</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="taskId">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" id="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea id="description" class="form-control"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Status</label>
              <select id="status" class="form-select">
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Priority</label>
              <select id="priority" class="form-select">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Due Date</label>
            <input type="date" id="due_date" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Tags</label>
            <select id="taskTags" class="form-select" multiple></select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('front_end/js/main.js') ?>"></script>
</body>
</html>
