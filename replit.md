# Empulse (Workfitdx) - Replit Setup

## Overview
Empulse (Workfitdx) is a multi-tenant Laravel 11 application with a Vue.js frontend designed for comprehensive employee lifecycle management. Its core purpose is to streamline company onboarding, employee management, and facilitate employee feedback through an integrated survey engine. The platform also includes a robust subscription billing system powered by Stripe. The project aims to provide a modern, scalable solution for businesses to manage their workforce and gather insights effectively.

## User Preferences
- Not yet configured

## System Architecture

### Core Technologies
- **Backend**: Laravel 11 (PHP 8.2)
- **Frontend**: Vue.js 3 with Vite
- **Database**: PostgreSQL (production), SQLite (development)
- **Styling**: Bootstrap 5, SASS
- **Charting**: Chart.js, vue-chartjs
- **Payments**: Stripe via Laravel Cashier

### UI/UX Decisions
- **Design System**: Modern Bootstrap 5 for consistent styling.
- **Authentication**: Redesigned authentication pages (login, register, password reset, email verification) with a consistent split-panel layout and purple gradient design. Includes Google OAuth integration.
- **Iconography**: Bootstrap Icons CDN for professional icon support.

### Technical Implementations & Design Choices
- **Multi-tenancy**: Designed as a multi-tenant application.
- **Dynamic URL Handling**: `AppServiceProvider` dynamically sets the application URL based on the request, ensuring correct asset paths and URL generation across different environments (local, Replit proxy, custom domain).
- **Asset Management**: Vite is used for frontend asset compilation. Production builds disable HMR and generate optimized, fingerprinted assets in `public/build/`.
- **Proxy Configuration**: `TrustProxies` middleware is configured to trust all proxies (`$proxies = '*'`) for compatibility with Replit's infrastructure.
- **Authorization**: Role-based access control (Admin, Manager, Chief, Team Lead, Employee) is implemented, with a `UserRole` enum for type-safe role management (though full integration is a known debt).
- **Survey System**: Features a built-in survey builder with various question types, wave-based deployment, automated drip cadences, and real-time analytics. Requires queue workers for automation.
- **User Management**: Includes CSV/XLSX import/export for bulk operations and a team member invitation system.
- **Billing**: Integration with Stripe via Laravel Cashier for subscription management.

### Deployment & Development Workflow
- **Development**: Laravel server runs on port 5000. Frontend assets require `npm run build` after changes. Database operations use `php artisan migrate` and `php artisan db:seed`.
- **Deployment**: Configured for autoscale deployment on Replit with a build step (`npm run build`) and a run command (`php artisan serve --host=0.0.0.0 --port=5000`).

## External Dependencies

- **Database**: PostgreSQL 16.10 (Replit's `helium` service)
- **Payment Gateway**: Stripe (via Laravel Cashier)
- **Email Service**: Brevo (for transactional emails, e.g., password resets, survey invitations)
- **Social Authentication**: Google OAuth, Facebook OAuth
- **Frontend Libraries**: Vue.js 3, Chart.js, vue-chartjs, Bootstrap 5, Bootstrap Icons