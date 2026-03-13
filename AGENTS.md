# AGENTS.md - Recruit Project

## Project Overview

Recruit is a self-hosted Corporate Recruitment System built with Laravel 10.x & FilamentPHP 3.x. It provides a complete applicant tracking system (ATS) with job postings, candidate management, and a candidate portal.

- **Tech Stack**: Laravel 10.x, PHP 8.1+, FilamentPHP 3.x, MySQL/SQLite
- **Documentation**: https://oss-admiral.gitbook.io/ossadmiral-recruit/
- **GitHub**: https://github.com/glauberportella/Recruit

## Current Features

### Admin Panel (Filament Admin)

| Feature | Description |
|---------|-------------|
| **Job Openings** | Create, edit, view, delete job postings with attachments |
| **Job Candidates** | Manage candidates per job opening with status tracking |
| **Candidate Profiles** | Global candidate profiles with resume/attachments |
| **Departments** | Manage organizational departments |
| **Referrals** | Employee referral management |
| **Users** | System user management with CRUD |
| **Roles & Permissions** | Role-based access control (Super Admin, Admin, Standard) |
| **Company Settings** | Configure company information |
| **User Profile** | Profile management with password update |
| **Authentication Logs** | Track user login/logout history |
| **User Impersonation** | Impersonate other users (Admin only) |

### Candidate Portal (`/portal/candidate`)

| Feature | Description |
|---------|-------------|
| **Job Openings** | Browse and view available job openings |
| **My Applied Jobs** | Track application status |
| **Saved Jobs** | Bookmark interesting jobs |
| **My Resume** | Upload and manage resume profile |
| **Account** | Manage account settings |

### Career Page (`/career`)

| Feature | Description |
|---------|-------------|
| **Landing Page** | Public job listings page |
| **Job Details** | Detailed job description view |
| **Application Form** | Apply to jobs via form (with reCAPTCHA/Turnstile) |

### Technical Features

- Role-based access control (Spatie Permissions)
- File attachments for jobs and candidates
- Theme support
- Authentication logging
- Email notifications (candidate portal invitations)
- Multi-auth (system users + candidate users)

---

## Future Features

### 1. AI Candidate-Job Matching

Automatically match candidates to suitable job openings using AI/ML.

#### Implementation Plan

**Phase 1: Data Preparation**
- [ ] Create migration for match scores table
- [ ] Add AI configuration settings (API keys, preferences)
- [ ] Implement candidate skills extraction from resume
- [ ] Implement job requirements parsing

**Phase 2: Matching Algorithm**
- [ ] Create `CandidateMatchingService` with AI integration
- [ ] Implement semantic matching using embeddings (OpenAI or local model)
- [ ] Add skill gap analysis
- [ ] Calculate match percentage score

**Phase 3: Admin Interface**
- [ ] Add "AI Match" button on Job Candidates page
- [ ] Display match scores with breakdown
- [ ] Show top matching candidates ranked
- [ ] Add "Suggest Jobs" for candidates

**Phase 4: Candidate Recommendations**
- [ ] Add "Recommended Jobs" section in Candidate Portal
- [ ] Add email notifications for new matching jobs

#### Suggested Stack
- OpenAI API (GPT embeddings) or open-source alternatives (Sentence Transformers)
- Laravel Jobs for async processing
- Store embeddings in database (vector similarity)

---

### 2. Online Interviews (Jitsi Integration)

Conduct video interviews directly within the platform using Jitsi Meet.

#### Implementation Plan

**Phase 1: Jitsi Integration**
- [ ] Create Jitsi configuration in settings
- [ ] Install Jitsi Meet SDK (iframe API)
- [ ] Create `JitsiService` for meeting management

**Phase 2: Interview Scheduling**
- [ ] Create `Interview` model and migration
- [ ] Add interview scheduling in Job Candidates
- [ ] Implement interview time slot management
- [ ] Add email/SMS notifications to candidates

**Phase 3: Meeting Interface**
- [ ] Create Jitsi meeting component/page
- [ ] Support scheduled meetings with unique URLs
- [ ] Add recording option (Jitsi cloud recording)
- [ ] Implement waiting room concept

**Phase 4: Interview Management**
- [ ] Add interview notes/feedback form
- [ ] Interview score/rating system
- [ ] Interview history timeline
- [ ] Calendar integration (Google Calendar, Outlook)

#### Jitsi Configuration
```env
JITSI_DOMAIN=meet.jit.si
JITSI_API_KEY=
JITSI_SECRET=
```

#### Meeting URL Pattern
```
{domain}//{room-name}?jwt={token}
```

---

## Development Commands

```bash
# Install dependencies
composer install
npm install && npm run build

# Setup
php artisan storage:link
php artisan migrate
php artisan db:seed
php artisan permissions:sync -C -Y
php artisan icons:cache

# Development
php artisan serve

# Testing
./vendor/bin/pest

# Code style
./vendor/bin/pint --dirty
```

## Default Credentials

- **Super User**: `superuser@mail.com` / `password`

## API Endpoints

See `routes/api.php` for REST API routes.

## Database Models

- `User` - System users
- `CandidateUser` - Candidate portal users
- `Candidates` - Candidate profiles
- `JobOpenings` - Job postings
- `JobCandidates` - Applications (pivot)
- `Departments` - Organization departments
- `Referrals` - Employee referrals
- `Attachments` - File attachments
- `SavedJob` - Bookmarked jobs
