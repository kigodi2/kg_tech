# NECTA Grading System - Color Codes Reference

## Grade Color Mapping

The following hex color codes are used by NECTA for competence level display:

| GPA Range | Grade | Competence | Hex Code | Color |
|-----------|-------|------------|----------|-------|
| 1-2.4 | **A** | Excellent | `#00AA7A` | Dark Green |
| 1.5-2.4 | **B** | Very Good | `#1FEE0B` | Bright Lime Green |
| 2.5-3.4 | **C** | Good | `#1FEE0B` | Bright Lime Green |
| 3.5-4.4 | **D** | Average | `#EF7043` | Orange |
| 4.5-5.4 | **E** | Satisfactory | `#DEF043` | Yellow-Green |
| 5.5-6.4 | **S** | Unsatisfactory | `#FF7272` | Light Red |
| 6.5-7 | **F** | Fail | `#FF272F` | Red |

## Implementation in Service

The colors are now integrated into the `NectaGradingService` class:

### Method: getGradeColor()

```php
$service = new NectaGradingService();

// Get color for grade
$color = $service->getGradeColor('A');  // Returns '#00AA7A'
$color = $service->getGradeColor('D');  // Returns '#EF7043'
$color = $service->getGradeColor('F');  // Returns '#FF272F'
```

### Using in Grading Reports

The `generateGradingReport()` method now includes color codes in each subject grade:

```php
$report = $service->generateGradingReport($candidate, 1, 2024);

// Each subject includes color
foreach ($report['subject_grades'] as $subject) {
    echo $subject['grade'];       // 'A'
    echo $subject['competence'];  // 'Excellent'
    echo $subject['color'];       // '#00AA7A'
}
```

## HTML Usage

### Blade Template Example

```blade
@foreach ($report['subject_grades'] as $subject)
    <tr>
        <td>{{ $subject['subject_name'] }}</td>
        <td>{{ $subject['marks_obtained'] }}</td>
        <td style="background-color: {{ $subject['color'] }}; padding: 10px;">
            {{ $subject['grade'] }} ({{ $subject['competence'] }})
        </td>
    </tr>
@endforeach
```

### Output Example

```html
<tr>
    <td>ENGLISH LANGUAGE</td>
    <td>85</td>
    <td style="background-color: #00AA7A; padding: 10px;">
        A (Excellent)
    </td>
</tr>

<tr>
    <td>MATHEMATICS</td>
    <td>65</td>
    <td style="background-color: #1FEE0B; padding: 10px;">
        C (Good)
    </td>
</tr>

<tr>
    <td>PHYSICS</td>
    <td>45</td>
    <td style="background-color: #EF7043; padding: 10px;">
        D (Average)
    </td>
</tr>
```

## CSS Styling

### Tailwind CSS Classes (Dynamic)

```blade
<td class="px-4 py-2" style="background-color: {{ $subject['color'] }}">
    {{ $subject['grade'] }}
</td>
```

### CSS with Color Variables

```css
:root {
    --necta-grade-a: #00AA7A;
    --necta-grade-b: #1FEE0B;
    --necta-grade-c: #1FEE0B;
    --necta-grade-d: #EF7043;
    --necta-grade-e: #DEF043;
    --necta-grade-s: #FF7272;
    --necta-grade-f: #FF272F;
}

.grade-cell {
    padding: 10px;
    font-weight: bold;
    border-radius: 4px;
}

.grade-a { background-color: var(--necta-grade-a); }
.grade-b { background-color: var(--necta-grade-b); }
.grade-c { background-color: var(--necta-grade-c); }
.grade-d { background-color: var(--necta-grade-d); }
.grade-e { background-color: var(--necta-grade-e); }
.grade-s { background-color: var(--necta-grade-s); }
.grade-f { background-color: var(--necta-grade-f); }
```

## API Response Example

```json
{
  "candidate_name": "John Doe",
  "subject_grades": [
    {
      "subject_name": "ENGLISH LANGUAGE",
      "marks_obtained": 85,
      "grade": "A",
      "competence": "Excellent",
      "color": "#00AA7A",
      "is_excluded": false
    },
    {
      "subject_name": "MATHEMATICS",
      "marks_obtained": 65,
      "grade": "C",
      "competence": "Good",
      "color": "#1FEE0B",
      "is_excluded": false
    },
    {
      "subject_name": "PHYSICS",
      "marks_obtained": 45,
      "grade": "D",
      "competence": "Average",
      "color": "#EF7043",
      "is_excluded": false
    }
  ]
}
```

## Color Display in Different Contexts

### Excel Export

When exporting to Excel, apply cell background color using the hex code:
```php
$sheet->setCellFill('C2', $subject['color']);
```

### PDF Export

When generating PDF reports, use the hex code for cell background:
```php
$pdf->setFillColor(
    hexdec(substr($subject['color'], 1, 2)),
    hexdec(substr($subject['color'], 3, 2)),
    hexdec(substr($subject['color'], 5, 2))
);
```

### Web Display

Simply apply the color hex code to the HTML element:
```html
<span style="background-color: {{ $subject['color'] }}; padding: 5px;">
    Grade {{ $subject['grade'] }}
</span>
```

## Notes

1. **Color Consistency**: These colors match the official NECTA results display exactly
2. **Accessibility**: Consider adding text contrast adjustments for better readability
3. **Printing**: Some colors may appear differently when printed; test accordingly
4. **Mobile**: Ensure colors are visible on mobile devices with reduced color intensity

## References

- Service Class: `app/Services/Results/NectaGradingService.php`
- Method: `getGradeColor(string $grade): string`
- NECTA Official: Colors used in ACSEE 2025 results reporting system
