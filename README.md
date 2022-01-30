<!-- PROJECT LOGO -->
<br />
<p align="center">
  <a href="https://github.com/othneildrew/Best-README-Template">
    <img src="https://raw.githubusercontent.com/BerkeAras/WorkGroup/main/src/static/logo.svg" alt="Logo" height="50">
  </a>

  <h3 align="center">WorkGroup API</h3>

  <p align="center">
    Self-Hosted Social Media for Companies
    <br />
    <a href="https://workgroup.berkearas.de/" target="_blank"><strong>Learn more »</strong></a>
    <br />
    <br />
    <a href="https://github.com/BerkeAras/WorkGroup/issues/new?assignees=&labels=bug&template=bug_report.md&title=%5BBUG%5D%3A+">Report Bug</a>
    ·
    <a href="https://github.com/BerkeAras/WorkGroup/issues/new?assignees=&labels=feature+request&template=feature_request.md&title=%5BFEAT%5D%3A+">Request Feature</a>
  </p>
</p>

<!-- TABLE OF CONTENTS -->
<details open="open">
  <summary>Contents</summary>
  <ol>
    <li>
      <a href="#about-the-project">About The Project</a>
      <ul>
        <li><a href="#built-with">Built With</a></li>
      </ul>
    </li>
    <li>
      <a href="#getting-started">Getting Started</a>
      <ul>
        <li><a href="#prerequisites">Prerequisites</a></li>
        <li><a href="#installation">Installation</a></li>
      </ul>
    </li>
    <li><a href="#usage">Usage</a></li>
    <li><a href="#roadmap">Roadmap</a></li>
    <li><a href="#contributing">Contributing</a></li>
    <li><a href="#license">License</a></li>
    <li><a href="#contact">Contact</a></li>
  </ol>
</details>

<!-- ABOUT THE PROJECT -->

## About The Project

WorkGroup is an open source, selfhosted social-media platform for companies.

Here's why:

-   Good networking is very important in companies.
-   Self hosted platforms are very important for companies with critical information.

### Built With

-   [Lumen](https://lumen.laravel.com/)
-   [JWT Auth](https://jwt.io/)

<!-- GETTING STARTED -->

## Getting Started

To get a local copy up and running follow these simple steps.

### Prerequisites

This is an example of how to list things you need to use the software and how to install them.

-   [Composer](https://getcomposer.org/doc/00-intro.md)

### Installation

1. Clone the repo
    ```sh
    git clone https://github.com/BerkeAras/WorkGroup-API.git
    ```
2. Install Composer packages
    ```sh
    composer install
    ```
3. Generate APP Key
    ```sh
    php artisan key:generate
    php artisan jwt:generate
    ```
4. Fill out `.env`
    ```env
    APP_ENV=production
    APP_DEBUG=false
    APP_KEY=[YOUR APP KEY]
    APP_TIMEZONE=UTC
    APP_URL=[YOUR WORKGROUP APP URL]

    DB_CONNECTION=mysql
    DB_HOST=[YOUR DATABASE HOST]
    DB_PORT=[YOUR DATABASE PORT]
    DB_DATABASE=[YOUR DATABASE NAME]
    DB_USERNAME=[YOUR DATABASE USERNAME]
    DB_PASSWORD=[YOUR DATABASE PASSWORD]

    JWT_SECRET=[YOUR JWT SECRET]

    API_PREFIX=api

    MAIL_DRIVER=smtp
    MAIL_HOST=[YOUR SMTP SERVER]
    MAIL_PORT=[YOUR SMTP PORT]
    MAIL_USERNAME=[YOUR SMTP USERNAME]
    MAIL_PASSWORD=[YOUR SMTP PASSWORD]
    MAIL_ENCRYPTION=[YOUR SMTP ENCRYPTION]
    MAIL_FROM_ADDRESS=[YOUR OUTGOING EMAIL]
    MAIL_FROM_NAME="[YOUR OUTGOING NAME]"

    BROADCAST_DRIVER=log
    CACHE_DRIVER=file
    SESSION_DRIVER=file
    SESSION_LIFETIME=120
    QUEUE_DRIVER=sync
    MAX_UPLOAD_SIZE=5
    ```
5. Create Database Schema
   ```
   php artisan migrate --seed
   ```
6. Run API
   ```
   php -S localhost:8000 -t public
   ```
   or use run-server.bat (windows), run-server.sh (unix)

7. Create following CronJobs:
    -    Call URL: `[API-URL]/api/jobs/onlineStatus` (every 30-60 minutes)
    if you run the API on shared hosting:
        -   Execute Code: `php artisan queue:work --timeout=60`
    if you can run processes:
        -   Run Code: `php artisan queue:listen`

<!-- ROADMAP -->

## Roadmap

See the [open issues](https://github.com/BerkeAras/WorkGroup/issues) for a list of proposed features (and known issues).

<!-- CONTRIBUTING -->

## Contributing

Contributions are what make the open source community such an amazing place to be learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

<!-- LICENSE -->

## License

Distributed under the MIT License. See [`LICENSE`](https://github.com/BerkeAras/WorkGroup/LICENSE) for more information.

<!-- CONTACT -->

## Contact

Berke Aras - [@brk_ars](http://instagram.com/brk_ars) - hello@berkearas.de

Project Link: [https://github.com/BerkeAras/WorkGroup](https://github.com/BerkeAras/WorkGroup)
