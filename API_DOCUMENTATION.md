# Winners API Endpoint

## Overview
This API endpoint exposes all winners for the current draw week.

## Endpoint
```
GET /api_winners.php
```

## Response Format

### Success Response (200 OK)
```json
{
    "status": "success",
    "message": "Current draw week winners retrieved successfully",
    "data": {
        "draw_week": {
            "id": 1,
            "date": "2025-11-16",
            "status": "current"
        },
        "winners": [
            {
                "id": 1,
                "name": "Joshua",
                "phone": "******4851",
                "draw_week": 1,
                "draw_date": "2025-11-16 18:00:00",
                "is_claimed": false,
                "claimed_at": null,
                "created_at": "2025-11-16 12:40:27",
                "status": "unclaimed"
            }
        ],
        "statistics": {
            "total_winners": 3,
            "claimed_count": 0,
            "unclaimed_count": 3,
            "claim_rate": 0
        }
    }
}
```

### Error Response (404 Not Found)
```json
{
    "status": "error",
    "message": "No current draw week found",
    "data": []
}
```

### Error Response (500 Internal Server Error)
```json
{
    "status": "error",
    "message": "Database error: [error details]",
    "data": []
}
```

## Data Fields

### Draw Week Information
- `id`: Draw week ID
- `date`: Draw date (YYYY-MM-DD)
- `status`: Current status of the draw week

### Winner Information
- `id`: Winner's unique ID
- `name`: Winner's name (HTML escaped)
- `phone`: Masked phone number (privacy protected)
- `draw_week`: Associated draw week ID
- `draw_date`: Date and time of the draw
- `is_claimed`: Boolean indicating if prize has been claimed
- `claimed_at`: Timestamp when prize was claimed (null if unclaimed)
- `created_at`: Timestamp when winner was added
- `status`: "claimed" or "unclaimed"

### Statistics
- `total_winners`: Total number of winners for current draw week
- `claimed_count`: Number of winners who have claimed their prize
- `unclaimed_count`: Number of winners who haven't claimed yet
- `claim_rate`: Percentage of winners who have claimed (0-100)

## Security Features
- Phone numbers are masked for privacy (only last 4 digits visible)
- HTML escaping for names to prevent XSS
- CORS headers enabled for cross-origin requests
- Proper error handling and status codes

## Usage Examples

### JavaScript (Fetch API)
```javascript
fetch('http://localhost/winnerclaim/api_winners.php')
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            console.log('Current draw week:', data.data.draw_week);
            console.log('Winners:', data.data.winners);
            console.log('Statistics:', data.data.statistics);
        } else {
            console.error('Error:', data.message);
        }
    })
    .catch(error => console.error('Fetch error:', error));
```

### PHP (cURL)
```php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/winnerclaim/api_winners.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$data = json_decode($response, true);

if ($data['status'] === 'success') {
    echo "Current draw week: " . $data['data']['draw_week']['date'] . "\n";
    echo "Total winners: " . $data['data']['statistics']['total_winners'] . "\n";
    echo "Claim rate: " . $data['data']['statistics']['claim_rate'] . "%\n";
} else {
    echo "Error: " . $data['message'] . "\n";
}

curl_close($ch);
```

### Command Line (curl)
```bash
curl -X GET http://localhost/winnerclaim/api_winners.php
```

## Notes
- The API automatically determines the current draw week based on the system's draw week logic
- Phone numbers are masked for privacy protection
- The endpoint returns winners in the order they were created
- Statistics are calculated dynamically based on the current data
