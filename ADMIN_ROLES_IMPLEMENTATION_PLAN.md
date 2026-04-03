# 🔐 Admin Role & Access Control System (RBAC)
## Implementation Plan & To-Do List

This document outlines the steps to transition from a single-admin system to a multi-role administrative environment where a Superadmin can manage sub-admins and their specific permissions.

---

## 🛠 Phase 1: Database & Model Preparation
**Goal:** Prepare the data structure to support roles and permissions.

- [x] **Migration: Add Role Support to Users Table**
  - Add `role_id` (foreign key) or `role` (string) column to `users`.
  - Add `is_superadmin` (boolean) to explicitly identify the primary owner.
- [x] **Migration: Create Roles Table**
  - Columns: `id`, `name` (e.g., "Manager", "Support"), `slug`, `permissions` (JSON format for easy mapping).
- [x] **Model Updates:**
  - Update `User.php` to include `role()` relationship.
  - Create `Role.php` model.
- [x] **Seeder:**
  - Create a "Superadmin" role with `*` (all) permissions.
  - Assign current admin(s) to the Superadmin role.

---

## 🔑 Phase 2: Access Control Logic (Middleware)
**Goal:** Create the engine that checks if a user can perform an action.

- [x] **Update `adminlogin` Middleware:** (Redundant as we refresh user in has_permission, but verified session)
- [x] **Create `CheckPermission` Middleware:**
  - Usage: `Route::middleware('permission:manage_users')`.
  - Logic: Compare requested permission against the JSON `permissions` array in the user's role.
- [x] **Global Helper Function:**
  - Implement `has_permission($permission_slug)` to be used in Controllers and Blade views.

---

## 🖥 Phase 3: Superadmin Management UI
**Goal:** Provide the interface for the Superadmin to manage the system.

- [x] **Role Management Screen (`admin/roles`):**
    - [x] List all roles.
    - [x] Create New Role form.
    - [x] Permission Matrix (Checkboxes for: `View Analytics`, `Manage Users`, `Approve Withdrawals`, `Edit Settings`, `Manage Rain`, etc.).
- [x] **Admin User Management (`admin/sub-admins`):**
    - [x] List only users where `isadmin = 1`.
    - [x] Form to "Create Admin" (Sets `isadmin = 1` and assigns a `role_id`).
    - [ ] Toggle to activate/deactivate sub-admin accounts. (Status displayed, manual DB toggle or future enhancement)

---

## 🛡 Phase 4: UI & Controller Integration
**Goal:** Enforce restrictions across the existing dashboard.

- [x] **Sidebar Filtering:**
  - Wrap sidebar links in `@if(has_permission('...'))` blocks.
- [x] **Controller Protection:**
  - Added `permission` middleware to `web.php` for all admin routes and API endpoints.
- [x] **Unauthorized Access Page:**
  - Standard Laravel 403 error handled by middleware.

---

## 📋 Permission Mapping (Reference)
| Module | Permission Slug | Description |
| :--- | :--- | :--- |
| **Users** | `view_users` | Access to User List |
| **Users** | `edit_users` | Change balances / passwords |
| **Finance** | `manage_deposits` | Approve/Reject Recharges |
| **Finance** | `manage_withdrawals` | Approve/Reject Withdrawals |
| **Game** | `game_settings` | Change multipliers and system limits |
| **Marketing** | `manage_rain` | Create/Cancel Rain giveaways |
| **Marketing** | `manage_freebets` | Distribute freebets |
| **System** | `full_access` | Superadmin only |

---

## 🚀 Execution Order (Recommended)
1. **DB Migration** (Add `role` column).
2. **Helper function** (`has_permission`).
3. **Sidebar UI** (Wrap existing links in permission checks).
4. **Role Management Interface** (To create new roles).
5. **Sub-admin Creator** (To invite other team members).
