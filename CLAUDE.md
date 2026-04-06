# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Mon ModuleAir is a French-language web interface for indoor air quality sensors (ModuleAir). Users authenticate with their device ID and token to view real-time and historical air quality data from their sensor. The interface also supports linking to outdoor NebuleAir sensors for indoor/outdoor comparison.

## Architecture

This is a single-page application contained entirely in `index.html` (no build process):

- **Frontend Stack**: Vanilla JavaScript, jQuery, Bootstrap 5.3, amCharts 5, Leaflet maps
- **Backend API**: `api.aircarto.fr` (external REST API for sensor data)
- **No local backend** - all data operations are API calls to aircarto.fr

### Key Components

1. **Authentication Form** (`#container_form`): Login with device ID + token
2. **Dashboard** (`#container_graphs`): Displays after successful authentication
   - Gauge charts (`chartdiv1-4`): Real-time PM1, PM2.5, PM10, CO2 readings
   - Time-series charts (`chartdiv5-8`): Historical data for PM, Temperature/Humidity/Pressure, COV, CO2
3. **Map** (`#map`): Leaflet map for geolocation of sensor
4. **Controls**: Room selection, NebuleAir linking, time range/resolution buttons

### API Endpoints Used

- `GET /capteurs/metadata` - Sensor metadata and current readings
- `GET /capteurs/dataModuleAir` - Historical ModuleAir data with time range and frequency
- `GET /capteurs/dataNebuleAir` - Historical NebuleAir data (outdoor comparison)
- `POST /capteurs/changeLoc` - Update sensor location
- `POST /capteurs/changeRoom` - Update sensor room assignment
- `GET /capteurs/link` - Link ModuleAir to NebuleAir

### Data Types

Measurements tracked: PM1, PM2.5, PM10 (µg/m³), CO2 (ppm), COV (ppm), Temperature (°C), Humidity (%), Pressure (hPa)

Time resolutions: 2m, 15m, 1h, 24h
History ranges: 1h, 3h, 24h, 48h, 1 week, 1 month, 1 year

## Development

No build process - edit `index.html` directly and serve via web server.

Static assets in `/img/` (logos only).

## Key Functions

- `submit()` - Main authentication and data loading flow
- `graphCreator()` - Creates amCharts time-series charts
- `gaugeCreator()` - Creates amCharts gauge charts for current readings
- `reloadDataSensor()` - Refreshes historical data with new time parameters
- `chooseTimeModuleAir()` - Handles time range/resolution button clicks
- `updateLocation()` / `updateLink()` / `changeRoom()` - API update operations
