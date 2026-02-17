# Task: Implement Dynamic Welcome Message and Configurable Icons

## Objective
Replace the static welcome message with a role-based welcome section. For students, display "Welcome {name}" with their profile picture, followed by a configurable grid of 10 icons (Icon + Name + URL) managed via plugin settings.

## Implementation Steps

### 1. Settings Configuration (`settings.php` & `lang/en/local_smartdashboard.php`)
- [ ] Add setting headings for "Student Dashboard Icons".
- [ ] Create 10 sets of configuration fields. Each set should include:
    -   `icon_name_X`: Text field for the label.
    -   `icon_class_X`: Text field for the FontAwesome class (or image URL).
    -   `icon_url_X`: Text field for the destination URL.
- [ ] Add corresponding language strings.

### 2. Backend Logic (`classes/output/dashboard.php`)
- [ ] In the `export_for_template` method:
    -   Determine if the current user is a student (or if the view should be the student view).
    -   Fetch user details: `fullname` and `profilereferenced` (profile picture URL).
    -   Retrieve the 10 icon settings.
    -   Construct an array of icons, filtering out empty ones.
    -   Pass `isstudent`, `userfullname`, `userprofilepic`, and `studenticons` array to the template.

### 3. Frontend Template (`templates/dashboard.mustache`)
- [ ] Modify the "Dashboard Banner" section.
- [ ] Add a conditional block `{{#isstudent}}` (or similar logic).
- [ ] Inside the student block:
    -   Display the user profile picture and "Welcome {name}".
    -   Render the icon grid below the welcome message. The design should match the provided screenshot (white cards with blue icons).
- [ ] Keep the existing "Welcome" message for non-students (or "Teachers").

### 4. Styling (`styles.css`)
- [ ] Add CSS for the user profile section (avatar sizing, layout).
- [ ] Add CSS for the icon grid:
    -   Flex/Grid layout for the items.
    -   Card styling (white background, rounded corners, shadow).
    -   Icon styling (color, size).
    -   Hover effects.

## Constraints & Preferences
-   **User Role**: Focus on "Student" role for this change.
-   **Settings**: 10 icons, each with Icon + Name + URL.
-   **Design**: "Rich Aesthetics", "Premium", "Dynamic" (hover effects).
-   **Avoid**: Hardcoding icons; use the settings.

