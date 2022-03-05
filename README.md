## Task

##installation

- ##Docker Way
    - Install [Docker](https://docs.docker.com/get-docker/)
    - Install [docker-compose](https://docs.docker.com/compose/install/)
    - make sure those ports are free [80, 3306, 8080, 443]
    - For the first run you have to build the image so run `sudo docker-compose up -d`.
    - To stop the project `sudo docker-compose down`.
    - To get into the container and run composer and artisan command run `sudo docker exec -it TDD-php8 bash`



- ##Local Serve Way
    - requirements
        - php ^8.0
        - composer
        - configure apache or nginx RootDir to public folder path of this project
        - mysql8
      
- ## Testing on host
    - use the attached postman collection `tdd.postman_collection` replace url env variable with the right one for you for docker-version it's http://localhost

- ## Testing local
    - I've attached the postman collection `tdd.postman_collection`

## Tools
- symfony5
- Docker
- docker composer
- phpunit
- php codesniffer linter
- postman for api testing

## Solution Description :
- Adding company with checking on unique swift code to stop adding fake ones


- Create invoice with pending status explained why in next step file
- when creating invoice, I add to debts table new record or update existing with invoice price
- I did a simple optimistic locking (no need to use lock-versioning) to update with condition that
total not exceeding the debtor limit in (DebtRepository::update)


- Pay invoice only with pending status marking it as paid and decreased the invoice price from
debtor debt total amount also added (optimistic locking preventing inconsistency) check
  (DebtRepository::decrease)

## Technical Points
- used Flix recommended folder structure as in microservices we need no complex bundles(HMVC) structure.
- Used TDD Approach to complete this challenge.
