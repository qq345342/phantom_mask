# Response

## A. Required Information
### A.1. Requirement Completion Rate
- [x] List all pharmacies open at a specific time and on a day of the week if requested.
  - Implemented at `GET /api/getPharmacies` API.
- [x] List all masks sold by a given pharmacy, sorted by mask name or price.
  - Implemented at `GET /api/getPharmacyMasks` API.
- [x] List all pharmacies with more or less than x mask products within a price range.
  - Implemented at `GET /api/getPharmaciesByMaskPriceAndAmount` API.
- [x] The top x users by total transaction amount of masks within a date range.
  - Implemented at `GET /api/getUsersByDateRange` API.
- [x] The total number of masks and dollar value of transactions within a date range.
  - Implemented at `GET /api/getMaskTransactionsData` API.
- [x] Search for pharmacies or masks by name, ranked by relevance to the search term.
  - Implemented at `GET /api/getPharmaciesOrMasks` API.
- [x] Process a user purchases a mask from a pharmacy, and handle all relevant data changes in an atomic transaction.
  - Implemented at `GET /api/getUser`, `POST /api/addUser`, `POST /api/updateUserCashBalance`, `POST /api/userPurchaseMasks` API.

### A.2. API Document
This documentation uses **Swagger UI** to display and interact with the API. Swagger UI provides an intuitive interface that allows you to explore the API, view detailed information about each endpoint, and execute requests.

- **Swagger UI** is an open-source front-end tool that automatically generates API documentation and allows users to test the API directly from the web interface.
- You can send requests and view responses right from the interface, making it easier to understand the API’s functionality.

Click the link below to access the Swagger UI interface:
Swagger UI Interface (http://localhost:YOUR_PORT/api/document)

### A.3. Install Laravel
1. **Clone the repository:**
```
git clone https://github.com/qq345342/phantom_mask.git
```
2. **Install Composer and NPM dependencies:**
```
cd phantom_mask
composer install 
npm install
```
3. **Set up your environment variables:**
copy .env.example to .env, and edit database settings.
4. **Generate the application key:**
```
php artisan key:generate
```
5. **Run migrations:**
```
php artisan migrate
```
6. **Serve the application:**
```
php artisan serve
```
### A.4. Import Data Commands
Please run these two script commands to migrate the data into the database.

```
php artisan app:import-pharmacies-data-command
php artisan app:import-users-data-command
```
## B. Bonus Information

## C. Other Information

### C.1. ERD

My ERD [erd-link](https://dbdiagram.io/d/67ed0fd64f7afba184122292).

### C.2. Technical Document

For frontend programmer reading, please check Swagger UI Interface (http://localhost:YOUR_PORT/api/document) to know how to operate those APIs.

- --
