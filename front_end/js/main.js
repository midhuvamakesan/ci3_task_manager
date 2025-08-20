document.addEventListener('DOMContentLoaded', function () {
    const API_URL = "http://localhost/ci3_task_manager/api/tasks";
    const API_TOKEN = "mysecrettoken123"; // must match backend token

    const taskTableBody = document.querySelector("#taskTable tbody");
    const pagination = document.getElementById("pagination");

    const searchInput = document.getElementById("search");
    const statusFilter = document.getElementById("statusFilter");
    const priorityFilter = document.getElementById("priorityFilter");
    const dueFrom = document.getElementById("dueFrom");
    const dueTo = document.getElementById("dueTo");
    const tagFilter = document.getElementById("tagFilter"); 
    const taskTagsInput = document.getElementById("taskTags"); 

    const taskForm = document.getElementById("taskForm");
    const taskModal = new bootstrap.Modal(document.getElementById("taskModal"));
    const taskIdInput = document.getElementById("taskId");
    const titleInput = document.getElementById("title");
    const descriptionInput = document.getElementById("description");
    const statusInput = document.getElementById("status");
    const priorityInput = document.getElementById("priority");
    const dueDateInput = document.getElementById("due_date");

    let currentPage = 1;
    const limit = 5;

    const api = axios.create({
        baseURL: API_URL,
        headers: {
            Authorization: `Bearer ${API_TOKEN}`,
            "Content-Type": "application/json"
        }
    });

    // Load tags
    async function loadTags() {
        try {
            const res = await axios.get("http://localhost/ci3_task_manager/api/tags", {
                headers: { Authorization: `Bearer ${API_TOKEN}` }
            });

            tagFilter.innerHTML = '<option value="">All</option>';
            taskTagsInput.innerHTML = '';

            res.data.forEach(t => {
                const option = document.createElement("option");
                option.value = t.id;
                option.textContent = t.name;
                tagFilter.appendChild(option);

                const option2 = document.createElement("option");
                option2.value = t.id;
                option2.textContent = t.name;
                taskTagsInput.appendChild(option2);
            });
        } catch (err) {
            console.error("Error loading tags:", err);
        }
    }

    // Load tasks
    async function loadTasks(page = 1) {
        currentPage = page;
        const params = new URLSearchParams({
            page,
            limit,
            status: statusFilter.value,
            priority: priorityFilter.value,
            keyword: searchInput.value,
            due_date_from: dueFrom.value,
            due_date_to: dueTo.value,
            tag_id: tagFilter.value
        });

        try {
            const res = await api.get(`?${params.toString()}`);
            const tasks = res.data.tasks || [];
            taskTableBody.innerHTML = '';

            if (tasks.length === 0) {
                const tr = document.createElement("tr");
                const td = document.createElement("td");
                td.colSpan = 6; 
                td.className = "text-center text-muted";
                td.textContent = "No data available";
                tr.appendChild(td);
                taskTableBody.appendChild(tr);
            } else {
                tasks.forEach(t => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${t.title}</td>
                        <td>${t.status}</td>
                        <td>${t.priority}</td>
                        <td>${t.due_date || '-'}</td>
                        <td>${t.tags.map(tag => tag.name).join(", ")}</td>
                        <td>
                            <button class="btn btn-sm btn-info me-1 toggle-status-btn">Toggle Status</button>
                            <button class="btn btn-sm btn-success me-1 edit-btn">Edit</button>
                            <button class="btn btn-sm btn-danger delete-btn">Delete</button>
                        </td>
                    `;
                    tr.querySelector(".toggle-status-btn").addEventListener("click", () => toggleStatus(t.id));
                    tr.querySelector(".edit-btn").addEventListener("click", () => editTask(t));
                    tr.querySelector(".delete-btn").addEventListener("click", () => deleteTask(t.id));
                    taskTableBody.appendChild(tr);
                });
            }

            renderPagination(res.data.total, page, limit);
        } catch (err) {
            console.error("Error loading tasks:", err);
            alert("Failed to load tasks.");
        }
    }

    function renderPagination(total, page, limit) {
        const pages = Math.ceil(total / limit);
        pagination.innerHTML = '';
        for (let i = 1; i <= pages; i++) {
            const li = document.createElement("li");
            li.className = `page-item ${i === page ? 'active' : ''}`;
            const a = document.createElement("a");
            a.href = "#";
            a.className = "page-link";
            a.textContent = i;
            a.addEventListener("click", e => {
                e.preventDefault();
                loadTasks(i);
            });
            li.appendChild(a);
            pagination.appendChild(li);
        }
    }

    function editTask(task) {
        taskIdInput.value = task.id;
        titleInput.value = task.title;
        descriptionInput.value = task.description || '';
        statusInput.value = task.status;
        priorityInput.value = task.priority;
        dueDateInput.value = task.due_date || '';

        const tagIds = task.tags.map(tag => tag.id.toString());
        Array.from(taskTagsInput.options).forEach(opt => {
            opt.selected = tagIds.includes(opt.value);
        });

        taskModal.show();
    }

    async function deleteTask(id) {
        if (!confirm("Are you sure you want to delete this task?")) return;
        try {
            await api.delete(`/${id}`);
            loadTasks(currentPage);
        } catch (err) {
            console.error("Error deleting task:", err);
            alert("Failed to delete task.");
        }
    }

    taskForm.addEventListener("submit", async e => {
        e.preventDefault();
        const id = taskIdInput.value;
        const payload = {
            title: titleInput.value,
            description: descriptionInput.value,
            status: statusInput.value,
            priority: priorityInput.value,
            due_date: dueDateInput.value,
            tags: Array.from(taskTagsInput.selectedOptions).map(opt => parseInt(opt.value))
        };

        try {
            if (id) {
                await api.put(`/${id}`, payload);
            } else {
                await api.post("", payload);
            }
            taskForm.reset();
            taskIdInput.value = '';
            taskModal.hide();
            loadTasks(currentPage);
        } catch (err) {
            console.error("Error creating/updating task:", err);
            alert("Failed to save task.");
        }
    });

    document.getElementById("filterForm").addEventListener("submit", e => {
        e.preventDefault();
        loadTasks(1);
    });

    // ---------------- Toggle Status ----------------
    async function toggleStatus(taskId) {
        try {
            // PATCH request to API
            const res = await api.patch(`/${taskId}/toggle-status`, {}); 
            alert(`Status updated to: ${res.data.new_status}`);
            loadTasks(currentPage);
        } catch (err) {
            console.error("Error toggling status:", err);
            alert("Failed to toggle status.");
        }
    }

    // Initial load
    loadTags();
    loadTasks();
});
