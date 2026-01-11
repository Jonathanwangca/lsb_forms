# Work Order System - Department-Based Approval Upgrade

## Overview

This upgrade changes the approval workflow from **role-based** (individual reviewers) to **department-based** (any team member can approve on behalf of their department).

## New Department Structure

| Dept Code | Department | Can Approve | Can Delete | Description |
|-----------|------------|-------------|------------|-------------|
| OPT | Operation | No | No | Creates WO requests, can resubmit after rejection |
| EXEC | Executive | Yes | No | Management approval |
| ACC | Accounting | Yes | No | Financial approval |
| ENG | Engineering | Yes | No | Technical approval |
| ADM | Administrator | No | Yes | System admin, can delete WOs |

## Database Migration

Run the SQL migration script to update your database:

```bash
mysql -u root -p lsb_forms < wko/sql/migration_dept_approval.sql
```

### What the migration does:
1. Creates `lsb_wo_dept_config` table for department configuration
2. Adds `title` and `dept_code` columns to `lsb_wo_users` table
3. Adds `reviewer_dept` and `reviewer_id` columns to `lsb_wo_review` table
4. Inserts default departments (OPT, EXEC, ACC, ENG, ADM)
5. Creates test users for each department (password: `password123`)

## Key Changes

### 1. User Management
- Users now have a **Title** field (optional) - e.g., "Project Manager", "Controller"
- Users must be assigned to a **Department** (required)
- Department replaces the old "Role" concept

### 2. Demo Mode
- Demo mode now switches between **departments** instead of individual roles
- Click on a department button (OPT, EXEC, ACC, etc.) to test as that department
- System automatically uses the first active user from that department

### 3. Approval Workflow
- When a WO is submitted, approval records are created for each department with `can_approve=1`
- Any member of a department can approve on behalf of that department
- All approving departments (EXEC, ACC, ENG) must approve for WO to be marked as DONE
- If any department rejects, WO status becomes REJECTED

### 4. Permissions

| Action | OPT | EXEC | ACC | ENG | ADM |
|--------|-----|------|-----|-----|-----|
| Create WO | Yes | Yes | Yes | Yes | Yes |
| Edit Draft (own) | Yes | Yes | Yes | Yes | Yes |
| Submit WO | Yes | Yes | Yes | Yes | Yes |
| Approve/Reject WO | No | Yes | Yes | Yes | No |
| Edit Rejected WO | Creator only | If rejected by dept | If rejected by dept | If rejected by dept | Always |
| Delete WO | No | No | No | No | Yes |

### 5. Inbox
- Inbox now shows WOs pending for your **department**
- Only visible if your department has approval rights
- Any department member can review and approve

## Test Users (Password: `password123`)

| Email | Name | Department |
|-------|------|------------|
| opt1@lsb.com | Operation User 1 | OPT |
| opt2@lsb.com | Operation User 2 | OPT |
| exec1@lsb.com | Executive User 1 | EXEC |
| exec2@lsb.com | Executive User 2 | EXEC |
| acc1@lsb.com | Accounting User 1 | ACC |
| acc2@lsb.com | Accounting User 2 | ACC |
| eng1@lsb.com | Engineering User 1 | ENG |
| eng2@lsb.com | Engineering User 2 | ENG |
| adm1@lsb.com | Admin User 1 | ADM |

## Testing Guide

1. **Login** with admin account
2. **Run the migration** SQL script
3. Go to **Department Config** to review department settings
4. Go to **User Management** to verify users have departments assigned
5. **Switch to OPT department** using Demo Mode
6. **Create a new WO** and submit it
7. **Switch to EXEC department** and approve the WO
8. **Switch to ACC department** and approve the WO
9. **Switch to ENG department** and approve the WO
10. Verify WO status becomes **DONE**

## Files Modified

### New Files
- `wko/sql/migration_dept_approval.sql` - Database migration
- `wko/dept_config.php` - Department management page
- `wko/api/dept_config.php` - Department API

### Modified Files
- `wko/includes/wo_auth.php` - Department-based authentication
- `wko/includes/wo_header.php` - Department switcher in demo mode
- `wko/includes/wo_functions.php` - Submit and review functions
- `wko/api/wo.php` - All WO API endpoints
- `wko/api/user.php` - User management API
- `wko/user_list.php` - User management page
- `wko/wo_inbox.php` - Review inbox
- `wko/wo_view.php` - WO detail view
- `wko/dashboard.php` - Dashboard statistics

## Rollback

If you need to rollback, you'll need to:
1. Drop the `lsb_wo_dept_config` table
2. Remove `title`, `dept_code` columns from `lsb_wo_users`
3. Remove `reviewer_dept`, `reviewer_id` columns from `lsb_wo_review`
4. Restore the original PHP files from version control
