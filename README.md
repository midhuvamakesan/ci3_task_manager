#  CI3 Task Manager
## Project Overview
A simple Task Management application built with CodeIgniter 3, supporting tasks with status, priority, due dates, and tags. It includes a RESTful API for tasks and tags, and a Bootstrap 5 frontend interface.

## Features
   - Add, edit, delete tasks
   - Tag management -Assign tags to tasks
   - Search with Keyword
   - Filter tasks by:
        - Status (Pending, In Progress, Completed)
        - Priority (Low, Medium, High)
        - Tags
   - Toggle task status: pending -> in_progress -> completed -> pending 
   - Task audit logs
   - RESTful API with token-based authentication
   - Pagination for task listing
   - Soft delete with restore functionality

## Requirements
   - PHP 7.4+ / 8.1
   - MySQL / MariaDB
   - XAMPP / WAMP / LAMP (or any local server)

## Technologies Used
   - PHP 7.4+ / 8.x
   - CodeIgniter 3
   - MySQL / MariaDB
   - Bootstrap 5
   - Axios for API requests
   - JavaScript / HTML / CSS

### Installation
    1. Clone the repository:
      - git clone https://github.com/midhu/ci3_task_manager.git
      - cd ci3_task_manager
    2.Copy files to your XAMPP htdocs folder.
    3.Database setup:
        -Create a new database, e.g., ci3_task_manager
        -Import database.sql included in the project
    4.Configure database in application/config/database.php


Import database schema

Import the provided database.sql file using phpMyAdmin or MySQL CLI.

Configure database

Edit application/config/database.php:

$db['default'] = array(
    'dsn'   => '',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'ci3_task_manager',
    'dbdriver' => 'mysqli',
    ...
);


Configure base URL

Edit application/config/config.php:

$config['base_url'] = 'http://localhost/ci3_task_manager/';


Enable mod_rewrite

Make sure .htaccess is in the root folder.

Apache mod_rewrite must be enabled.

Sample .htaccess:

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /ci3_task_manager/

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>

Usage
Frontend

Open: http://localhost/ci3_task_manager/

Add, edit, delete tasks

Filter tasks and toggle status

API

Token Authentication: Authorization: Bearer mysecrettoken123

Method	Endpoint	Description
GET	/api/tasks	List tasks
POST	/api/tasks	Create task
GET	/api/tasks/{id}	Get task details
PUT	/api/tasks/{id}	Update task
DELETE	/api/tasks/{id}	Delete task
PATCH	/api/tasks/{id}/toggle-status	Cycle task status
PATCH	/api/tasks/{id}/restore	Restore deleted task

Tags API:

Method	Endpoint	Description
GET	/api/tags	List tags
POST	/api/tags/store	Create tag
GET	/api/tags/{id}	Tag details
PUT	/api/tags/update/{id}	Update tag
DELETE	/api/tags/delete/{id}	Delete tag
Frontend JS Example (Axios)
const api = axios.create({
    baseURL: 'http://localhost/ci3_task_manager/api/tasks',
    headers: { Authorization: 'Bearer mysecrettoken123' }
});

// Toggle task status
await api.patch(`/5/toggle-status`);

Notes

API will return 404 if task does not exist or is deleted.

Soft-deleted tasks can be restored using the /restore endpoint.

Task status cycles through: pending → in_progress → completed → pending.

License

MIT License © [Your Name]