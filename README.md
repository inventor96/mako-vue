# Mako-Vue
An opinionated boilerplate for web applications using PHP/Mako on the backend and Vue.js on the frontend.

This is my personal starter template for building web applications with Mako and Vue.js. It aims to provide a solid foundation with common features and best practices, allowing developers to focus on building their applications rather than setting up the initial structure. It's been a common starting point for my own projects, so I'm sharing it in hopes that it can be useful to others as well.

## Features & Stack
- [Mako](https://makoframework.com/) PHP Framework
- Environment-specific configuration via hierarchical pattern with Mako's built-in config system
- Common Mako templating engine add-ons
- Persistent storage tailored to MySQL/MariaDB databases
- Database migrations via built-in Mako migration system, including separate user for running migrations
- [mako-mailer](https://github.com/inventor96/mako-mailer) for email sending and templating
- [Inertia.js](https://inertiajs.com/) for seamless server-driven SPA experience
- [intertia-mako](https://github.com/inventor96/inertia-mako) adapter
- [Vue.js 3](https://vuejs.org/) with Composition API and Single File Components
- [Vite](https://vite.dev/) for fast development and build process
- [Vue DevTools](https://devtools.vuejs.org/) for easier frontend debugging
- [Bootstrap](https://getbootstrap.com/) for responsive UI
- [Bootstrap Icons](https://icons.getbootstrap.com/) for iconography
- [Bootswatch](https://bootswatch.com/) for easy theming
- Common pre-built components and layout for Vue.js
- Basic authentication scaffolding
- IDE helpers for VSCode
- Optional Dockerization for consistent development and deployment
	- Separation of dev and prod environments by utilizing compose.override.yml
	- Separate services for PHP, MariaDB, and Node/Vite
	- File ownership and permissions handling for seamless host-container interaction*
	- Pre-configured for Xdebug with VSCode integration*
	- [Mailpit](https://mailpit.axllent.org/) for local email testing*
	- [Adminer](https://www.adminer.org/) for database management*
	- Host networking allows local domain name usage*
	- Allows concurrent projects to run locally without the need for port separation or reverse proxies*

\* Local development only.

## How to Use It
Please see the [wiki](https://github.com/inventor96/mako-vue/wiki) for detailed instructions on setting up and using this boilerplate.

## Limitations & To-Dos
- Local development environment is optimized for Linux hosts. Other OSes may require additional configuration.
- Production readiness (security, performance optimizations, etc.) in a Docker context has not been fully tested.
- Vue DevTools cannot open files in an IDE running on the host while using Docker.
- Refactor email class to be a service rather than a model.
- Move NavLinks class to be a service rather than a model?
- Create Mako console command(s) to scaffold new Mako-Vue projects. (e.g. session cookie setup, security/encryption keys, etc.)
- Configure Composer stuff for easier installation as a project.
	- Console commands
	- NPM install?
	- Prompt for network setup script?
	- Add/remove common changes in .gitignore?