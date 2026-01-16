# SavannahDB 

**A High-Performance, Flat-File RDBMS in Pure PHP.**

SavannahDB is a custom Relational Database Management System built from scratch to demonstrate advanced PHP file manipulation, memory management, and data structure implementation. It operates without any external database dependencies (like MySQL or SQLite), using strictly typed PHP 8.2 code to manage data persistence.

---

## Key Features

*   **⚡ O(1) Performance:**
    *   **Writes:** Append-only log architecture (NDJSON) ensures constant-time inserts.
    *   **Reads:** In-Memory Byte-Offset Indexing allows `O(1)` Primary Key lookups via `fseek`.
*   **ACID Compliance (Partial):** Uses `flock()` (File Locking) for atomic operations and concurrent write safety.
*   **Memory Efficient:** Utilizes PHP `Generators` (`yield`) to stream large datasets without loading them entirely into RAM.
*   **SQL Engine:** Custom Regex-based parser supporting `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `JOIN`, and `ORDER BY`.
*   **Compaction:** Includes a `VACUUM` command to physically remove soft-deleted rows and reclaim disk space.

---

## Architecture

SavannahDB follows a modular layered architecture, separating the Query Interface from the Storage Engine.

```mermaid
graph TD;
    User[User / Laravel App] -->|SQL Query| REPL[Console REPL / Controller];
    REPL -->|Parse| Parser[Database.php (SQL Parser)];
    Parser -->|Command| Engine[Table.php (Storage Engine)];
    Engine -->|Lock & Seek| Index[In-Memory Index (RAM)];
    Engine -->|Read/Write| File[(NDJSON Storage File)];
```

---

## Complexity Analysis

| Operation | Complexity | Description |
| :--- | :--- | :--- |
| **Insert** | **O(1)** | Appends to the end of the file and updates the RAM index. |
| **Find (ID)** | **O(1)** | Uses the index to jump instantly to the file byte offset. |
| **Delete** | **O(1)** | Soft-delete (removes from RAM index immediately). |
| **Full Scan** | **O(N)** | Generators allow linear scanning with constant memory usage. |
| **Join** | **O(N*M)** | Nested-Loop Join. (Intentionally simple implementation). |
| **Update** | **O(N)** | Requires atomic rewrite (Copy-on-Write) to ensure safety. |

---

## Installation & Usage

### 1. Interactive CLI (REPL)
Interact with the database directly from your terminal.

```bash
php console.php
```

**Supported Commands:**
```sql
CREATE TABLE users
INSERT INTO users (name, role) VALUES ('Alice', 'Admin')
SELECT * FROM users WHERE id = 1
SELECT * FROM users JOIN orders ON users.id = orders.user_id
VACUUM users
```

### 2. Laravel Web Interface
A demo Laravel application is included to visualize the database in action.

```bash
cd savannah_web
php artisan serve
```
Visit `http://localhost:8000` to manage students, assign grades, and view joined reports.

---

## Why PHP?

This project was built to demonstrate that PHP is not just a templating language, but a capable systems language when used correctly. By implementing a database engine in pure PHP, we highlight:
*   **Streams & I/O:** precise control over file pointers and locking.
*   **Memory Management:** handling large data via iterators.
*   **Data Structures:** implementing indexes and parsers manually.

---

*Built for the "Build a Database from Scratch" Challenge.*
