# Polaris Compute Resources Dashboard

A Laravel-based dashboard for browsing and managing compute resources with advanced filtering capabilities. This is an independent replica of the Polaris Dashboard compute page with comprehensive filtering, sorting, and API integration.

## 🚀 Features

### Core Functionality
- **Real-time Resource Display**: Browse GPU and CPU compute resources with live data
- **Advanced Filtering System**: 5-layer filtering with toggle controls
- **Smart Sorting**: GPU-first with memory-based sorting, CPU by core count  
- **Responsive Design**: Mobile-first design with Tailwind CSS
- **Dark/Light Mode**: System preference detection with manual toggle
- **Search & Export**: Client-side search with JSON export functionality

### Filtering System
1. **API-Level Filtering**: Removes fake GPUs, invalid storage, unverified/inactive resources
2. **UI Safety Filter**: Double-checks verification status  
3. **Monitoring Health Filter**: Only shows resources with healthy auth/connection/docker
4. **User Interface Filters**: Type (GPU/CPU/All) and ownership (All/Mine)
5. **System Filter Toggles**: Enable/disable individual filters via UI

### API Integration
- **Polaris API**: Full integration with `https://polaris-interface.onrender.com`
- **Real-time Updates**: Auto-refresh every 15 seconds
- **Caching**: 30-second TTL with force refresh capability
- **Error Handling**: Fallback endpoints and graceful degradation

## 📋 Requirements

- PHP 8.1+
- Laravel 10.x
- Node.js 18+
- MySQL/PostgreSQL
- Composer

## 🛠 Installation

### 1. Clone and Setup
```bash
git clone https://github.com/bigideaafrica/polaris-compute-dashboard.git polariscompute
cd polariscompute
composer install
npm install
```

### 2. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 4. Asset Compilation
```bash
npm run build
# or for development
npm run dev
```

### 5. Start Development Server
```bash
php artisan serve
```

Visit `http://localhost:8000` to see the dashboard.

## ⚙️ Configuration

### Environment Variables
```env
# Polaris API Configuration
POLARIS_API_BASE_URL=https://polaris-interface.onrender.com
POLARIS_API_KEY=dev-services-key
POLARIS_SERVICE_KEY=9e2e9d9d4370ba4c6ab90b7ab46ed334bb6b1a79af368b451796a6987988ed77
POLARIS_SERVICE_NAME=miner_service

# Filter Options (true/false)
ENABLE_FAKE_GPU_FILTER=true
ENABLE_STORAGE_FILTER=true
ENABLE_VERIFICATION_FILTER=true
ENABLE_MONITORING_FILTER=true
```

### Filter Configuration
Filters can be toggled via the UI or programmatically:

```php
// Toggle a filter
FilterSetting::toggleFilter('fake_gpu_filter');

// Update filter config
FilterSetting::updateFilterConfig('monitoring_filter', [
    'required_auth_status' => 'ok',
    'required_docker_running' => true
]);
```

## 🎯 Usage

### Basic Navigation
- **Homepage**: Browse all compute resources
- **Type Filters**: Quick filters for GPU/CPU/All
- **Search**: Real-time search across resource names and specs
- **Export**: Download current filtered results as JSON

### Filter Controls
- **System Filters**: Toggle individual filters in the sidebar
- **Quick Filters**: GPU/CPU type selection in header
- **Advanced Filters**: Memory range, core count, location filters
- **Reset**: Clear all filters and return to default view

### Keyboard Shortcuts
- `R` or `Ctrl+R`: Refresh resources
- `F`: Toggle filter panel
- `/`: Focus search input
- `ESC`: Close modals/overlays

## 🔧 API Endpoints

### Web Routes
- `GET /` - Main dashboard
- `GET /compute` - Alternative dashboard route  
- `GET /resources` - Legacy route

### API Routes
- `GET /api/compute` - Fetch filtered resources
- `GET /api/compute/{id}` - Get specific resource
- `POST /api/compute/refresh` - Force refresh from Polaris API
- `GET /api/compute/export` - Export resources data
- `POST /api/filters/{name}/toggle` - Toggle filter
- `POST /api/filters/reset` - Reset all filters

## 📊 Data Models

### ComputeResource
```php
$resource = [
    'id' => 'uuid',
    'resource_type' => 'GPU|CPU',
    'gpu_specs' => [...],
    'cpu_specs' => [...],
    'ram' => '32GB',
    'storage' => ['total_gb' => 1000, 'type' => 'SSD'],
    'validation_status' => 'verified|pending|rejected',
    'is_active' => true,
    'monitoring_status' => [...],
    'rental_status' => [...]
];
```

### Filter Statistics
```php
$stats = [
    'original_count' => 979,
    'final_count' => 156,
    'total_excluded' => 823,
    'filters_applied' => [
        ['name' => 'fake_gpu_filter', 'excluded_count' => 315],
        ['name' => 'verification_filter', 'excluded_count' => 245],
        // ...
    ]
];
```

## 🧪 Testing

### API Connectivity Test
```bash
curl -X GET "http://localhost:8000/api/compute/test"
```

### Filter Statistics
```bash
curl -X GET "http://localhost:8000/api/compute/stats/filters"
```

### Manual Resource Refresh
```bash
curl -X POST "http://localhost:8000/api/compute/refresh" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-token"
```

## 🔍 Debugging

### Enable Debug Mode
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```

### Check Filter Status
The dashboard displays real-time filter statistics including:
- Total resources from API
- Resources after each filter
- Excluded count per filter
- Final displayed count

## 🚀 Deployment

### Production Setup
1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Configure proper database credentials
3. Set up SSL/HTTPS
4. Configure caching (Redis recommended)
5. Set up queue workers for background tasks

### Performance Optimization
- Enable API response caching
- Use Redis for session/cache storage
- Implement CDN for static assets
- Configure Laravel Octane for high performance

## 📈 Monitoring

### Health Checks
- Database connectivity
- Polaris API status
- Filter processing performance
- Resource load times

### Metrics Tracked
- Total API requests
- Filter usage statistics
- Resource view counts  
- Export download counts

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

For issues and questions:
1. Check the logs: `storage/logs/laravel.log`
2. Test API connectivity: `/api/compute/test`
3. Verify filter configuration in database
4. Check browser console for JavaScript errors

## 🔄 Updates

To update the dashboard:
```bash
git pull origin main
composer install --no-dev
npm install
npm run build
php artisan migrate
php artisan cache:clear
```

---

**Built with Laravel 10, Tailwind CSS, Alpine.js, and ❤️**
