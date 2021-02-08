Solido Symfony Example Application
==================================

Fully functional example application using Solido with Symfony integration

ToDo list API application built using [Solido suite](https://solid-o.github.io/docs/) to
show how a fully functional API application is written with solido.  
The application has an healthcheck endpoint (GET /_healthz), user rest endpoints and
task rest endpoints.

## Versions

The used version must be passed in "X-API-Version" request header.

- 1.0
- 1.1

## Endpoints

### GET /users (all versions)

Gets the user list, eventually filtered by name and email.
See [Query Language documentation](https://solid-o.github.io/docs/#/query-language)
for more information about the list.

### GET /user/{id} (all versions)

View the user details.

The following fields are returned in response:

- `_id` - URN of the user
- `name` - The full name of the user
- `email` - The email of the user (as set in creation). Is set to null if not admin (or request user different by user entity)
- `password` - Always null, is set only when creating a user with a random password
- `creation` - Read-only. Creation date-time.

### POST /users (all versions)

Create a new user. Can be called only from admin users.  
A form is used for deserialization and data binding. If a validation error is raised, 400 status code
is returned, otherwise a 201 is returned.

In case an entity is created, the details view is returned in response body, with `password`
field set to a random password string.

### PATCH /user/{id} (all versions)

Edits the user entity. Can be only called by admin users.
Supports merge-patch and json patch. See [Patch manager documentation](https://solid-o.github.io/docs/#/patch-manager)
for more information about PATCH request handling.

### GET /task/{id} (all versions)

View the task details.
The following fields are returned in response:

- `_id` - URN of the task
- `title` - The title (or short description) of the task
- `description` - The long description of the task and its details
- `assignee` - URN of the assignee user for this task
- `creation` - Read-only. Creation date-time
- `due_date` - Date time. Only available in version 1.1

### POST /tasks (all versions)

Creates a new task entity.  
`title` and `assignee` are required fields in all versions.  
`due_date` is only available in version 1.1

201 status code is returned if no validation error is raised.

### PATCH /task/{id} (all version)

Edits a task. All fields of creation is available for edit.  
Only admins or assignee can make modifications.  
Non-admins users cannot modify assignees.

### GET /tasks (only available in version 1.1)

Gets a list of tasks.
