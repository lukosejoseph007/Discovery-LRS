# xAPI Statement Parameters

## 1. Verb
**What to Add:** The action you're tracking, e.g., "viewed", "answered", "completed".
**Where to Add It:** The first parameter in the function call.
**Example:** "viewed" indicates that the user viewed a specific slide or content.

## 2. Verb ID
**What to Add:** A unique identifier (usually a URL) that represents the verb.
**Where to Add It:** The second parameter in the function call.
**Example:** "http://adlnet.gov/expapi/verbs/viewed" is the standard xAPI URL for the "viewed" verb.

## 3. Object
**What to Add:** A human-readable name or description of the content or action being tracked.
**Where to Add It:** The third parameter in the function call.
**Example:** "Slide 1" might represent the first slide of your course.

## 4. Object ID
**What to Add:** A unique identifier (usually a URL) for the content being tracked.
**Where to Add It:** The fourth parameter in the function call.
**Example:** "http://example.com/course/slide1" is the URL that uniquely identifies "Slide 1" in your course.

## 5. Object Type
**What to Add:** The type of object you're tracking, e.g., "Activity".
**Where to Add It:** The fifth parameter in the function call.
**Example:** "Activity" is a standard xAPI object type representing a learning activity.

## 6. Timestamp
**What to Add:** The exact time when the event occurred.
**Where to Add It:** The sixth parameter in the function call.
**Example:** `new Date().toISOString()` generates the current timestamp in ISO format, which is the standard for xAPI.

## 7. Result Data (Optional)
**What to Add:** Any additional result data, such as scores, success/failure indicators, or response times.
**Where to Add It:** The seventh parameter in the function call (an empty object `{}` can be used if no result data is needed).
**Example:** `{ "score": { "scaled": 0.8 } }` if you want to track a score.

## 8. Course ID
**What to Add:** A unique identifier (usually a URL) for the course.
**Where to Add It:** The eighth parameter in the function call.
**Example:** "http://example.com/course" is the URL that uniquely identifies your course.

## 9. Attempt ID
**What to Add:** A unique identifier for the user's attempt at the course or activity.
**Where to Add It:** The ninth parameter in the function call.
**Example:** "attempt-001" could be a string you generate to track different attempts.

## 10. Registration ID
**What to Add:** A unique identifier that groups multiple statements related to the same learning experience.
**Where to Add It:** The tenth parameter in the function call.
**Example:** "registration-001" could be a string you generate to group all statements related to a specific registration of the course.






## Copy this to your storyline Execute Javascript trigger

The full `sendStatement` function call would look like this:

sendStatement(
    "viewed",
    "http://adlnet.gov/expapi/verbs/viewed",
    "Slide 1",
    "http://example.com/course/slide1",
    "Activity",
    new Date().toISOString(),
    {},
    "http://example.com/course",
    "attempt-001",
    "registration-001"
);

## Example Function Call

The full `sendStatement` function call would look like this:

```javascript
sendStatement(
    "viewed",                                      // Verb
    "http://adlnet.gov/expapi/verbs/viewed",       // Verb ID
    "Slide 1",                                     // Object
    "http://example.com/course/slide1",            // Object ID
    "Activity",                                    // Object Type
    new Date().toISOString(),                      // Timestamp
    {},                                            // Result Data (empty if not used)
    "http://example.com/course",                   // Course ID
    "attempt-001",                                 // Attempt ID
    "registration-001"                             // Registration ID
);
