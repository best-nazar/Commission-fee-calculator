# Symfony CLI application
## Commission fee calculation

### Instalation
1. Clone the Repository
2. Create .env file
3. Add to .env LAYER_API_KEY='' for https://api.apilayer.com/exchangerates_data/latest API Calls
4. Install dependencies. Run command:
```
composer install
```

### Testing
1. Unit test command:
```
php bin/phpunit tests
```

### Run the calculation:
1. CD to project dir
2. run the console command:
```
 php bin/console app:calculate tests/input.csv
```

### APP configuration
```
config/services.yaml
```

### Task Description
Application allows private and business clients to deposit and withdraw funds to and from accounts in multiple currencies. Clients may be charged a commission fee.

#### Commission fee calculation
Commission fee is always calculated in the currency of the operation. For example, if you withdraw or deposit in US dollars then commission fee is also in US dollars.
Commission fees are rounded up to currency's decimal places. For example, 0.023 EUR should be rounded up to 0.03 EUR.
#### Deposit rule
All deposits are charged `0.03%` of deposit amount.

#### Withdraw rules
There are different calculation rules for withdraw of private and business clients:

- Private Clients

Commission fee - `0.3%` from withdrawn amount.
1000.00 EUR for a week (from Monday to Sunday) is free of charge. Only for the first 3 withdraw operations per a week. 4th and the following operations are calculated by using the rule above (0.3%). If total free of charge amount is exceeded them commission is calculated only for the exceeded amount (i.e. up to 1000.00 EUR no commission fee is applied).

- Business Clients

Commission fee - `0.5%` from withdrawn amount.

#### Input
See tests/input.csv for data input example.

#### Output
Printed to console.