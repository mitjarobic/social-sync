<?php

namespace App\Models;

use App\Support\TimezoneHelper;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    /**
     * Get an attribute from the model.
     * Overrides the default Eloquent method to convert date attributes to user's timezone.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        // Check if the attribute is a date and convert it to user's timezone
        if ($value instanceof \Illuminate\Support\Carbon) {
            // Only convert if the attribute is a date field
            // This ensures we don't convert non-date fields that happen to be Carbon instances
            if (in_array($key, $this->getDates())) {
                return TimezoneHelper::toUserTimezone($value);
            }
        }

        return $value;
    }

    /**
     * Get the attributes that should be converted to dates.
     * Overrides the default Eloquent method to include all date fields.
     *
     * @return array
     */
    public function getDates()
    {
        $dates = parent::getDates();

        // Add any date fields from the $casts array
        if (property_exists($this, 'casts')) {
            foreach ($this->casts as $key => $value) {
                if (in_array($value, ['date', 'datetime', 'timestamp'])) {
                    $dates[] = $key;
                }
            }
        }

        return array_unique($dates);
    }
}
