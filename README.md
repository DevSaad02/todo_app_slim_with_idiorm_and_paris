# To-Do List App

A simple, interactive To-Do List web application that allows users to add, update, delete, and rearrange tasks dynamically.

## Features
- Add new tasks with a description.
- Mark done tasks
- Change color of list
- Drag & drop to reorder tasks.
- Delete tasks and automatically adjust positions.
- Persistent storage using MySQL.
- AJAX-based interactions for a seamless user experience.

## Tech Stack
- **Frontend:** HTML, CSS, JavaScript, jQuery, Ajax  
- **Backend:** PHP
- **Database:** MySQL  
- **Framework:** Slim
- **ORM:** Idiorm with paris

## Installation & Setup
1. Clone this repository:
   ```bash
   git clone https://github.com/DevSaad02/todo_app_slim_with_idiorm_and_paris.git
   cd todo_app_slim_with_idiorm_and_paris

## Import the database:

- Create a MySQL database.
- Import the todo_app.sql file provided in the project root directory.
- Update env if applicable according to your configuration

## Start a local PHP server

- php -S localhost:8000 -t public
