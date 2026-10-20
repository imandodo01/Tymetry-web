# Tymetry — Todo Management System

A Laravel-based task manager focused on a practical todo lifecycle: create and manage tasks, prioritize work, track due dates, and archive or restore completed/old items.

## Current status

The core MVP is implemented. The project is in a testing and refinement stage; the README and Notion project notes are being reconciled against the current code. Feature presence in the code does not by itself mean every workflow has been manually tested.

## Features implemented in the current code

- Authentication routes for login and logout, plus forgot-password/reset-password and OTP flows.
- Profile details and password update routes.
- Todo creation, editing, status updates, and archiving.
- Archived todo list and restore action.
- Bulk mark-done, bulk archive, and bulk restore endpoints.
- Inline status, priority, and due-date updates.
- Filtering by title, status, and priority.
- Smart ordering that prioritizes overdue and due-today tasks, then priority and due date.
- Due-date labels and visual states for overdue, today, tomorrow, and upcoming tasks.
- Dashboard counts for total, pending, in-progress, and done tasks; due-date lists; and recent activity.
- Activity-log records for key todo actions.

## Stack

- PHP 8.3+
- Laravel 13
- MySQL (configure the database in `.env`)
- Vite and Tailwind CSS tooling
- Yajra Laravel DataTables
- JavaScript and Blade views

## Local setup

Requirements: PHP version compatible with `composer.json`, Composer, Node.js/npm, and a configured database.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure the database connection in `.env`, then run:

```bash
php artisan migrate
npm install
npm run build
php artisan serve
```

Alternatively, inspect the Composer `setup` script for the repository's bundled setup sequence.

## Tests

Run the automated suite with:

```bash
php artisan test
```

Review the existing test coverage before treating the core workflows as fully verified. The code review that informed this README was a source inspection, not a test run.

## Repository scope

This repository contains the Laravel todo-management application. The separate `Tymetry-spa` repository describes a React/Vite company-profile and portfolio SPA; it is not the Laravel todo application.
