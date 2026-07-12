# Wan2GP API Server

This project provides an API server for **Wan2GP**, allowing you to generate music through a REST API.

The API has been tested with **Wan2GP** using a valid `wgp_config.json` configuration file.

## Wan2GP Repository

The original Wan2GP project can be found here:

https://github.com/deepbeepmeep/Wan2GP

## Requirements

Before using the API server:

- Install and configure Wan2GP.
- Complete the Wan2GP setup so that a `wgp_config.json` file is created.
- Copy the provided launcher files into the **root directory** of your Wan2GP installation.

### Windows

Copy the following files into the Wan2GP root folder:

- `.py`
- `.bat`

### Linux

Copy the following files into the Wan2GP root folder:

- `.py`
- `.sh`

## Default Port

The API server runs on:

```
http://localhost:8001
```

---

# API Endpoints

| Method | Endpoint | Description |
|---------|----------|-------------|
| POST | `/create_task` | Create a generation task and return a `task_id`. |
| POST | `/create_task_raw` | Create a task using raw tags or text from external websites. |
| POST | `/parse_tags` | Convert raw tags into a structured prompt and caption. |
| GET | `/task_status/{id}` | Check task status, progress, and output information. |
| POST | `/release_task/{id}` | Release and clean up a finished task. |
| GET | `/get_result/{id}` | Download the generated output file. |
| POST | `/cancel_task/{id}` | Cancel a running task. |
| GET | `/list_tasks` | List all current tasks. |
| POST | `/generate` | Alias for `/create_task`. |
| POST | `/run` | Submit a task and wait until it finishes before returning the result. |

---

# Remote Access

The API server supports both **local** and **remote** connections.

### Local

No authentication is required when connecting locally.

### Remote

When remote access is enabled, the server automatically creates a file named:

```
.api_auth.json
```

This file contains the generated:

- Username
- Password

These credentials are required to connect to the API remotely.

---

# Wan2GP Website

A PHP-based website is included that allows users to generate music through the API server.

---

# Installer

An installer is included in the `install` folder.

The installer is written in **C#** and automatically:

- Installs Wan2GP
- Checks whether **Miniconda3** is installed
- Checks whether **Git** is installed
- Installs missing requirements when needed
- Configures everything required to get started

> **Note:** Wan2GP will not work correctly without both **Miniconda3** and **Git**.

---

# Launchers

Inside the **Things to put into Wan2GP** folder you will find:

- **Wan2GP API Launcher**
- **Wan2GP WebUI Launcher**

These optional launchers can be copied into the Wan2GP root folder.

They allow you to easily:

- Start the API server
- Start the normal Wan2GP WebUI
- Manage both launch modes independently

---

# Features

- REST API for Wan2GP
- Music generation support
- Local and remote access
- Automatic remote authentication
- Task queue management
- Progress tracking
- Result downloads
- Task cancellation
- Windows and Linux support
- Simple installer included****
