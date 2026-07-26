This guide explains a common professional Git workflow used by many technology companies and open-source projects. It is not only a naming style. It is a shared language between developers, tools, CI systems, release automation, and future maintainers.

## 1. Conventional Commits

Conventional Commits define a clear format for `git commit` messages.

Instead of writing vague messages like:
```bash
git commit -m "changes"
git commit -m "update stuff"
```

you write messages that explain the type of change:
```bash
git commit -m "feat: add login form"
git commit -m "fix: resolve token refresh issue"
git commit -m "docs: update setup instructions"
```

### Basic Format

```txt
<type>[optional scope]: <description>
```
Examples:
```txt
feat(auth): add login form
fix(api): handle expired access token
docs: update README setup steps
```

The `scope` is optional. It describes the area of the project affected by the change, such as `auth`, `api`, `dashboard`, or `editor`.

### Common Commit Types

| Type       | Meaning                                                          | Example                                    |
| ---------- | ---------------------------------------------------------------- | ------------------------------------------ |
| `feat`     | Adds a new feature                                               | `feat: add project creation flow`          |
| `fix`      | Fixes a bug                                                      | `fix: prevent crash on empty project list` |
| `docs`     | Documentation-only change                                        | `docs: add Git workflow guide`             |
| `style`    | Formatting-only change that does not affect behavior             | `style: format sidebar component`          |
| `refactor` | Improves code structure without adding a feature or fixing a bug | `refactor: simplify project query hooks`   |
| `chore`    | Tooling, dependency, or maintenance work                         | `chore: update npm dependencies`           |
| `test`     | Adds or updates tests                                            | `test: add auth hook tests`                |

## 2. Git Branch Naming Strategy

Branch names describe the purpose of the work before anyone opens the code.

Instead of using unclear names like:
```txt
new-work
my-branch
updates
```

use names with clear prefixes:
```txt
feature/login-page
bugfix/form-validation
hotfix/payment-crash
release/v1.2.0
```

### Common Branch Prefixes

| Prefix     | Use Case                          | Example                       |
| ---------- | --------------------------------- | ----------------------------- |
| `feature/` | Building a new feature            | `feature/payment-integration` |
| `bugfix/`  | Fixing a bug in development       | `bugfix/currency-conversion`  |
| `hotfix/`  | Fixing an urgent production issue | `hotfix/login-outage`         |
| `release/` | Preparing a release               | `release/v1.2.0`              |
| `chore/`   | Maintenance or tooling work       | `chore/update-eslint-config`  |
| `docs/`    | Documentation work                | `docs/git-workflow-guide`     |

### Practical Example

Imagine you are adding payment integration to an app.

Create a feature branch:
```bash
git checkout -b feature/payment-integration
```

After adding the first version of the feature:
```bash
git commit -m "feat: add stripe payment gateway"
```

If you fix a bug while working on the same branch:
```bash
git commit -m "fix: resolve currency conversion issue"
```

The branch name explains the goal of the work. The commit messages explain the specific changes made along the way.

## Recommended Rules

Use these simple rules when starting:
- Use `feature/` for new features.
- Use `bugfix/` for normal bug fixes.
- Use `hotfix/` only for urgent production bugs.
- Start commit messages with a clear type such as `feat`, `fix`, `docs`, or `chore`.
- Keep commit descriptions short and direct.
- Write commit messages in the imperative style when possible, such as `add login form` instead of `added login form`.

Good examples:
```txt
feat: add dashboard page
fix: handle missing user avatar
docs: explain environment variables
chore: update vite config
```

Weak examples:
```txt
update
fixes
changes
final version
```

## What MUST be in a PR Description:

- What does this do?
- Why is this needed?
- How to test?
- Screenshots (if UI change)
