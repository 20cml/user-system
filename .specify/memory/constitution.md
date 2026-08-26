<!--
Sync Impact Report
Version change: (none) → 1.0.0
Modified principles: N/A (initial ratification)
Added sections:
  - Core Principles: Simplicity & Convention over Configuration, Security by Default,
    Spec-Driven Development, Test Coverage for Core Flows, Explainable Code
  - Technology Constraints
  - Development Workflow
  - Governance
Removed sections: N/A
Deferred TODOs: none
-->

# User System Constitution

## Core Principles

### I. Simplicity & Convention over Configuration
Follow standard Laravel conventions rather than introducing custom patterns. Prefer
the framework's built-in solutions (Eloquent, Blade, validation, routing) over
custom abstractions. Every design decision must be explainable in one sentence by
the author; if it can't, simplify it.
Rationale: This is a learning project meant to be fully understood by its author,
not just functional — cleverness that obscures understanding works against the
project's purpose.

### II. Security by Default (NON-NEGOTIABLE)
Passwords MUST always be hashed, never stored or logged in plain text. All input
MUST be validated server-side, regardless of client-side validation. CSRF
protection MUST remain enabled on all state-changing routes. Secrets (`.env`
values, OAuth client secrets) MUST NEVER be committed to version control.
Rationale: This system's core purpose is authentication; a security lapse here
undermines the entire project.

### III. Spec-Driven Development
Every feature MUST progress through the Spec Kit workflow — specify → plan →
tasks → implement — before being considered complete. Each stage MUST be
committed to git with a message that reflects what that stage produced.
Rationale: The project's explicit goal is to document, via git history, how each
feature was specified, planned, implemented, and tested — this is not optional
process overhead, it is a deliverable.

### IV. Test Coverage for Core Flows
Registration (email/password and Google), login (both methods), logout, viewing
profile, and updating profile MUST each have automated tests covering their
primary path before being considered done.
Rationale: Automated tests provide objective proof the system works, rather than
relying on manual spot-checks.

### V. Explainable Code
Code should read as clearly as possible to someone learning the codebase. Avoid
unexplained "magic," excessive indirection, or dependencies that aren't
justified by an actual requirement.
Rationale: The author must be able to explain any part of this system to
reviewers/stakeholders without treating it as a black box produced by AI.

## Technology Constraints

Backend: Laravel (PHP), following the currently installed framework major version.
Database: MySQL.
Authentication scaffolding: Laravel Breeze (Blade + lightweight controllers/views).
Google login: Laravel Socialite.
No additional frameworks, state-management libraries, or infrastructure
components may be introduced unless a specific requirement in an approved spec
justifies them.

## Development Workflow

Each feature is developed on its own branch, created via the Spec Kit `/speckit-specify`
step. Specs, plans, and tasks are reviewed by the author before moving to the next
stage. Implementation work is checked against this constitution's principles
before a feature is considered complete. Commit messages should make the
development stage (spec, plan, tasks, implementation) identifiable from the git
log alone.

## Governance

This constitution supersedes ad hoc technical decisions made during
implementation. Amendments are made by editing this document, incrementing the
version per semantic versioning (MAJOR: incompatible principle removal/redefinition;
MINOR: new principle or materially expanded guidance; PATCH: clarifications and
wording fixes), and updating the Last Amended date. Any deviation from a
principle during implementation must be justified in the relevant spec or plan
document.

**Version**: 1.0.0 | **Ratified**: 2026-08-25 | **Last Amended**: 2026-08-25
