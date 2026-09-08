Standards Alignment Tool (SALT)
===============================

[![Latest Stable Version](https://poser.pugx.org/opensalt/opensalt/v/stable)](https://github.com/opensalt/opensalt) [![Test Status](https://github.com/opensalt/opensalt/workflows/Build%20and%20Test/badge.svg?branch=develop)] [![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=opensalt_opensalt&metric=alert_status)](https://sonarcloud.io/dashboard?id=opensalt_opensalt)


Overview
--------

OpenSALT is an open-source Learning and Employment Record (LER) registry
platform developed by Public Consulting Group in partnership with its
public-sector clients.  It enables organizations to define, manage,
align, publish, and exchange competencies, standards, credentials,
learning opportunities, jobs, pathways, and issuer information using
open standards and interoperable APIs.  OpenSALT has evolved from a
competency framework management system into a standards-based registry
service designed to support modern digital credentialing ecosystems.

Built on the extensibility of 1EdTech CASE® and aligned with emerging
LER, credentialing, and verifiable credential standards, OpenSALT serves
as a trusted source of semantic metadata that can be consumed by awarding
systems, digital wallets, transcript services, learner record platforms,
student information systems, workforce applications, and verification
services.  The platform enables organizations to create rich relationships
among competencies, credentials, learning opportunities, occupations,
and pathways, providing a foundation for skills-based education and
workforce development initiatives.

OpenSALT supports both interactive administration through a web
application and headless integration through secure REST APIs.
The platform is designed for multi-tenant deployments, regional and
statewide registries, consortium-based governance models, and enterprise
implementations that require a shared registry of trusted issuers and
awardable achievements.

### Key Capabilities

- Competency and standards framework management
- Learning and Employment Record (LER) registry services
- Trusted issuer and organization registry management
- Credential, learning opportunity, and job definition services
- Pathway modeling and semantic alignment management
- CASE® 1.1-compatible content structures and extensions
- Standards-based import, export, and publishing services
- Secure REST APIs for system-to-system integration
- Multi-tenant governance and access control
- Support for digital credential, transcript, wallet, and verification ecosystems

### Open Standards First

OpenSALT is committed to interoperability and open ecosystems.
The platform is designed to support and align with standards including:

- 1EdTech CASE®
- IEEE 1484.2 Learning and Employment Records (LER)
- Open Badges 3.0
- Comprehensive Learner Record (CLR)
- Credential Engine CTDL
- CEDS-LER Profiles
- W3C Verifiable Credentials (VCs)
- Decentralized Identifiers (DIDs)

### Mission

OpenSALT helps education, workforce, credentialing, licensing, and
employment organizations create transparent, connected, and interoperable
learning and career pathways.  By providing a standards-based registry
foundation, OpenSALT enables institutions and communities to build
learner-centered ecosystems where achievements, skills, credentials,
and opportunities can be discovered, trusted, exchanged, and verified
across organizational boundaries.


Installation
------------

The code is intended to run using a set of docker containers using
docker-compose so that it can be easily deployed in any Linux environment
with docker installed.

1. Install Docker from [here](https://www.docker.com/products/docker)
   and Docker Compose from [here](https://docs.docker.com/compose/install/)
  * Note that a new user group, `docker`, has been created. The user that will interact with the Docker service will need to be in this group.
  * Ensure to set the MySQL folder permissions: `chmod -R 777 core/docker/data/mysql`
  * Also set the cache directory permssions: `chmod 777 core/var/cache`

2. Create .env file
  ```
  cp .env.dist .env
  ```

3. Edit .env and set desired values (optional)

4. Start the application
  ```
  make up
  ```
    * To stop the application

    ```
    make down
    ```

5. Install libraries with composer/yarn and build application
  ```
  make force-build
  ```

6. Run database migrations
  ```
  make migrate
  ```

7. http://_ip-address of web container_/ should show the initial screen with debug turned on


8. One will also need to create the administrative account and password for the system with the **super-user** role:
  - To create an access group use `./core/bin/console salt:group:add [group name]`
  - To create a user use `./core/bin/console salt:user:add [username] [--password="secret"] [--role="rolename"]`


Other Docs
----------

- [User Management Commands](./core/docs/Commands.md)
