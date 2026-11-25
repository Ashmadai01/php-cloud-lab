# Testing Log

## 2026-11-25 — Authentication manual end-to-end checks

**Project:** CampusX  
**Branch:** `main`

### Automated regression tests

- Test suite result previously verified after merging PR #2: **168 passed, 0 failed**.

### Manual authentication checks

The following checks were completed in the running application:

| Check | Observed result |
|---|---|
| Sign in with the account after password reset | Passed — Dashboard displayed the welcome message |
| Password reset flow | Passed — the account was able to sign in after resetting its password |
| Change password | Passed — application displayed “Password changed” |
| Revoke other device sessions | Passed — application reported that 3 other devices were signed out |
| Sign out | Passed — accessing a protected page displayed “Please sign in to continue.” |
| Protected Dashboard access while signed out | Passed — unauthenticated access was redirected to the sign-in flow |
| Sign in again and return to Dashboard | Passed — Dashboard worked normally |

### Account/database diagnostics used during troubleshooting

- Database connection succeeded.
- The connected database contained 4 user records at the time of inspection.
- The target account was present, verified, and not suspended.
- The supplied password did not match the stored password hash before the reset. The user then reset the password and successfully signed in.
- No passwords, password hashes, reset tokens, or full email addresses are recorded here.

### Scope and notes

These results confirm the tested authentication paths in this environment. They do not constitute a complete security audit or prove that every authentication edge case has been tested.

The Xdebug connection warning appeared in terminal output during diagnostics; it did not prevent the database check from succeeding.
