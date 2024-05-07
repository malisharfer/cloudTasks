# **Users management**

This project is a site for managing requests to create new users in Azure.

## Description

Due to the large number of requests to set up new users in Azure and the lack of personnel with appropriate permissions to handle these requests, it became necessary to develop a platform that would centralize the requests and enable handling in an accessible and fast manner.

In order to facilitate the management of requests to open new users in azure, a website is being opened that will allow the submission of requests to open a user in an accessible manner and the centralization of processing requests for the responsible manager - including the creation of actual users and updating the requester on the status of the end of processing the request (approved/rejected).

## Dependencies

The following dependencies are required for the project:

- laravel
- filament
- pgsql

## Running the code

The code is deployed to azure web-app and is available as a site for those with appropriate permissions.
Azure Web Apps is a specific type of Azure App Service focused on hosting web applications. Azure Web Apps provides a fully managed platform for building and hosting web applications.

The project is packaged into a Docker image and deployed to a web-app where it is run in a container and that's how it works.

The construction of the various resources in Azure was done using Terraform, which is responsible for building resources in Azure.

The code is automatically deployed to the various functions-app through the CI/CD process.

## Tests

The code has undergone in-depth TEST tests with respect to end situations.