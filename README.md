MTA Subway Route Finder
===

_Built by <a href="https://cwest144.com/" target="_blank">Chris West</a>


## Requirements
* PHP 8.1+
* [Composer](https://getcomposer.org)
* Redis
* PostgreSQL


## Setup
* Install packages: `composer install`
* Create a database for the application
* Run the database migrations with `php artisan migrate` and seed the database with `php artisan db:seed`.
* Set up and fill out environment variables: `cp .env.example .env`. Make sure the `DB_*`, `MTA_*` and `PYTHON_PATH` variables are filled in. This application uses a python executable to interface with the MTA API, so `PYTHON_PATH` should be the path to your installed python, for example `/opt/homebrew/opt/python@3.10/bin/python3.10`. Get an MTA API key <a href="https://api.mta.info/#/landing/" target="_blank">here</a>.


## USAGE
* Local use of the application can be done by starting a local server with `php artisan serve`.

## ENDPOINTS

### Find routes between two stations with trip end time: `/api/trip`

Include `start` and `end` keys in the query parameters, where each is a station ID from the `id` column of the `Stations` table. This will return an array of possible `trip`s between the start and end stations. Each `trip` will include an array of `tripSegments` in order, essentially an array of lines to take and stations to transfer at in order to complete the trip. The `trip` also includes an `endTime` which is the estimated completion time of the `trip` in `GMT` using real time MTA data and including transfer time between stations, if the trip begins at the time of the request. The results are ordered by soonest `endTime`s first.

An example response with `start = F20` (Bergen Street -- F, G) and `end = A46` (Nostrand Ave -- A, C):

```json
{
    "status": "success",
    "data": [
        {
            "trip": [
                {
                    "station": "F20",
                    "name": "Bergen St"
                },
                {
                    "train": "G"
                },
                {
                    "station": "A42",
                    "name": "Hoyt-Schermerhorn Sts"
                },
                {
                    "train": "C"
                },
                {
                    "station": "A46",
                    "name": "Nostrand Av"
                }
            ],
            "endTime": "2023-01-18T22:47:44.000000Z"
        },
        {
            "trip": [
                {
                    "station": "F20",
                    "name": "Bergen St"
                },
                {
                    "train": "F"
                },
                {
                    "station": "A41",
                    "name": "Jay St-MetroTech"
                },
                {
                    "train": "A"
                },
                {
                    "station": "A46",
                    "name": "Nostrand Av"
                }
            ],
            "endTime": "2023-01-18T22:49:30.000000Z"
        },
        {
            "trip": [
                {
                    "station": "F20",
                    "name": "Bergen St"
                },
                {
                    "train": "G"
                },
                {
                    "station": "A42",
                    "name": "Hoyt-Schermerhorn Sts"
                },
                {
                    "train": "A"
                },
                {
                    "station": "A46",
                    "name": "Nostrand Av"
                }
            ],
            "endTime": "2023-01-18T22:49:30.000000Z"
        },
        {
            "trip": [
                {
                    "station": "F20",
                    "name": "Bergen St"
                },
                {
                    "train": "F"
                },
                {
                    "station": "A41",
                    "name": "Jay St-MetroTech"
                },
                {
                    "train": "C"
                },
                {
                    "station": "A46",
                    "name": "Nostrand Av"
                }
            ],
            "endTime": "2023-01-18T22:55:09.000000Z"
        }
    ]
}
```

### Find upcoming arrivals at a station: `/api/arrivals/{stationId}`

Use a `stationId` from the `id` column of the `Stations` table. An example response using the station `A46` (Nostrand Ave):

```json
{
    "status": "success",
    "data": {
        "A": {
            "A46S": [
                "1674080091",
                "1674079720",
                ...
                "1674086130",
                "1674086370"
            ],
            "A46N": [
                "1674079791",
                "1674080631",
                ...
                "1674085440",
                "1674084300"
            ]
        },
        "C": {
            "A46S": [
                "1674080091",
                "1674080812",
                ...
                "1674085300",
                "1674086100"
            ],
            "A46N": [
                "1674079724",
                "1674080670",
                ...
                "1674083090",
                "1674083940"
            ]
        }
    }
}
```
The response is structured as line designation > platform (north or south bound) > upcoming arrivals (seconds since 00:00:00 1/1/1970).