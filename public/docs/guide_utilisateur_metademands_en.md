# User Guide — GLPI Metademands Plugin

## 1. Overview

The **Metademands** plugin is a complex request / multi-ticket form builder for GLPI. It allows administrators to define structured forms ("meta-demands") that, when submitted by end users through a wizard, automatically create one or more GLPI tickets (or problems, changes), along with child tickets and tasks in a defined workflow.

**Core concepts:**

- A **Meta-demand** is a named form template linked to one or more ITIL categories. It contains fields, tasks, conditions, and configuration.
- **Fields** are the form questions presented to the user (text, dropdowns, checkboxes, dates, uploads, etc.).
- **Tasks** define what is created on submission: a ticket, a sub-meta-demand, a task on the parent ticket, or an email.
- The **Wizard** is the end-user interface for selecting and filling in a meta-demand.
- **Step-by-step mode** allows a form to be filled progressively by different groups/users in sequence, with notifications.
- **Conditions** control whether fields are shown or hidden based on values entered elsewhere in the form.
- **Field options** (FieldOptions) attach actions to specific field values: trigger a task, show/hide a block, require a validator.
- **Drafts** allow users to save a partially filled form and resume it later.
- **Basket mode** enables quantity-based ordering from a reference catalogue.

---

## 2. Rights Management

Path: `Administration > Profiles > Meta-Demands tab`

### 2.1 CRUD Rights (standard matrix)

| Right | Description |
|-------|-------------|
| `plugin_metademands` | Main access: view, create, edit, delete meta-demands + access to the wizard |
| `plugin_metademands_followup` | Manage inter-ticket followups |

### 2.2 Binary Rights (on/off)

| Right | Description |
|-------|-------------|
| `plugin_metademands_createmeta` | Use the wizard to submit a meta-demand |
| `plugin_metademands_validatemeta` | Approve/refuse pending meta-demand validations |
| `plugin_metademands_fillform` | Fill a step in a step-by-step form |
| `plugin_metademands_cancelform` | Cancel or delete a submitted form |
| `plugin_metademands_publicforms` | Mark a form as public |
| `plugin_metademands_updatemeta` | Edit form values from the ticket view |
| `plugin_metademands_on_login` | Automatically redirect to the wizard on login (helpdesk interface) |
| `plugin_metademands_in_menu` | Hide the button in the helpdesk menu |

---

## 3. Global Configuration

Path: `Configuration > Meta-Demands > Configuration` (requires `config` UPDATE right)

### 3.1 Main Options

| Setting | Description |
|---------|-------------|
| Redirect simple ticket → meta-demand | Automatically redirect to the wizard when a ticket ITIL category matches a meta-demand |
| Parent ticket prefix | Text prepended to parent ticket titles |
| Child ticket prefix | Text prepended to child ticket titles |
| Parent content in children | Child tickets inherit the parent ticket content |
| Create PDF | Automatically generate a PDF summary on submission |
| Use drafts | Enable the save-for-later draft feature |
| Display mode | Show meta-demands as icon tiles (instead of a list) |
| Technician language | Force a specific language for technician-facing notifications |
| Show top meta-demands | Display a "top meta-demands" section |
| Incident type icon | Custom icon for incident-type meta-demands |
| Request type icon | Custom icon for request-type meta-demands |
| Problem type icon | Custom icon for problem-type meta-demands |
| Change type icon | Custom icon for change-type meta-demands |
| Groups by regex | Enable adding groups by regular expression pattern |

### 3.2 Service Catalogue Options

| Setting | Description |
|---------|-------------|
| Show list in Service Catalog | Show meta-demands in the ServiceCatalog plugin widget |
| Service Catalog widget title | Title displayed in the widget |
| Service Catalog widget comment | Description shown in the widget |
| Service Catalog widget icon | Tabler icon for the widget |

### 3.3 Configuration Tabs

| Tab | Content |
|-----|---------|
| **Main** | Options listed above |
| **Tools** | Administrative maintenance actions |
| **Schema check** | Database table integrity verification |

---

## 4. Meta-demand Structure

### 4.1 General Properties

| Field | Description |
|-------|-------------|
| Name | Form name displayed in the wizard |
| Comment | Short description (visible in the wizard) |
| Description | Long description (rich text) |
| Entity / Recursive | Entity scope |
| Active | Available to users |
| Maintenance mode | Temporarily disables the form |
| Object to create | `Ticket`, `Problem` or `Change` |
| Type | Request type filter (Incident, Request, etc.) |
| ITIL categories | Linked categories (JSON array, multiple allowed) |
| Form category | Grouping in the GLPI 11 service catalogue |
| Icon | Tabler icon class for display |
| Illustration | Image for the service catalogue |
| Pinned | Display at the top of the wizard list |
| Template | Mark as a template (not visible to end users) |

### 4.2 Advanced Options

| Option | Description |
|--------|-------------|
| Step-by-step mode | Sequential filling by different groups |
| Create a single ticket | Create one parent ticket (vs one per task) |
| Force task creation | Create GLPI tasks rather than child tickets |
| Validation before child tickets | Require approval before child ticket creation |
| Allow update | Let users edit form values after submission |
| Allow cloning | Allow users to clone/re-submit the form |
| Hide empty blocks | Hide blocks with no visible fields |
| Hide title | Do not display the meta-demand title in the wizard |
| Colours | Visual customisation (title, background) |
| Show rules | Display conditional display rules to users |
| Initial requester in children | Copy the requester to child tickets |
| Basket mode | Enable the order/basket mode |
| Confirmation step | Show a confirmation step before final submission |

### 4.3 Meta-demand Form Tabs

| Tab | Content |
|-----|---------|
| **Main** | General properties and advanced options |
| **Fields** | Manage the form fields |
| **Wizard overview** | Preview the form |
| **Step-by-step config** | Step-by-step settings (if enabled) |
| **Step-by-step blocks** | Assign blocks to groups (if enabled) |
| **Ticket fields** | Predefined/mandatory GLPI fields for the parent ticket |
| **Translations** | Multilingual names and descriptions |
| **Task creation** | Define child tickets/tasks/emails |
| **Group rights** | Restrict visibility to authorised groups |
| **Conditional displays** | Field condition rules |
| **Export** | XML export of the form |
| **Log** | Change history (central interface) |

---

## 5. Field Types

### 5.1 Display-only Fields

| Type | Description |
|------|-------------|
| `title` | Section heading (display only) |
| `title-block` | Block-level heading |
| `informations` | Information/notice panel |

### 5.2 Text Fields

| Type | Description |
|------|-------------|
| `text` | Single-line text. Supports regex validation. Can be auto-filled from the user's profile (phone, mobile, staff number). |
| `textarea` | Multi-line text. Supports rich text (TinyMCE). |
| `tel` | Telephone number |
| `email` | Email address |
| `url` | URL |

### 5.3 Choice Fields

| Type | Description |
|------|-------------|
| `yesno` | Yes/No toggle button |
| `checkbox` | Multiple checkboxes (custom values). Can trigger tasks or show/hide blocks per value. |
| `radio` | Radio buttons (single select, custom values). |

### 5.4 Dropdown Fields

| Type | Description |
|------|-------------|
| `dropdown` | Dropdown from a GLPI table (Location, User category, etc.). Display modes: classic, split, block. |
| `dropdown_object` | GLPI itemtype selector (User, Group, any GLPI asset). Auto-fills requester info when a User is selected. |
| `dropdown_meta` | Dropdown with admin-defined custom values. Supports field options. |
| `dropdown_multiple` | Multi-select with custom values. |
| `dropdown_ldap` | Dropdown populated from an LDAP query (configurable server, attribute, filter). |
| `parent_field` | Inherits values from a parent meta-demand field. |

### 5.5 Numeric Fields

| Type | Description |
|------|-------------|
| `number` | Numeric input |
| `range` | Slider range input |

### 5.6 Date/Time Fields

| Type | Description |
|------|-------------|
| `date` | Date picker. Options: future dates only, use today as default, add N days offset. |
| `time` | Time picker |
| `datetime` | Date + time picker |
| `date_interval` | Two date pickers (start/end interval) |
| `datetime_interval` | Two date+time pickers |

### 5.7 File/Media Fields

| Type | Description |
|------|-------------|
| `upload` | File upload. Configurable maximum number of files. |
| `signature` | Signature pad (canvas). Saved as an image. |
| `link` | Clickable hyperlink (display only, configurable URL) |

### 5.8 Special Types

| Type | Description |
|------|-------------|
| `basket` | Shopping basket field. References the `Basketobject` catalogue. Allows quantity selection. |
| `freetable` | Free-form table with configurable columns. Users can add rows dynamically. |

### 5.9 Special Objects Available in `dropdown_object`

- `urgency` — Ticket urgency
- `impact` — Ticket impact
- `priority` — Ticket priority
- `mydevices` — User's own equipment

---

## 6. Field Configuration

### 6.1 Basic Parameters

| Parameter | Description |
|-----------|-------------|
| Label | Text displayed next to the field |
| Mandatory | The form cannot be submitted if this field is empty |
| Regex | Validation regular expression |
| Full-width display | The field occupies the full block width |
| Colour | Display colour |
| Icon | Associated icon |
| Read-only | Value displayed but not editable |
| Hidden | Invisible field (value passed in the background) |

### 6.2 Advanced Parameters

| Parameter | Description |
|-----------|-------------|
| Default value | Pre-filled value when the form opens |
| Auto-fill from requester | Pre-fill from the requester's GLPI profile (phone, mobile, etc.) |
| Auto-fill from supervisor | Pre-fill from the requester's supervisor profile |
| Map to parent ticket | Field value populates a GLPI parent ticket field |
| Map to child ticket | Field value populates a child ticket field |
| Link to User field | Pre-fill from the user selected in another field |
| Future dates only | (date field) Disallow past dates |
| Use today's date | (date field) Use today as default value |
| Day offset | (date field) Add N days to the default date |
| Rich text | (textarea) Enable TinyMCE editor |
| Max files | (upload) Maximum number of files allowed |
| Display mode | (dropdown) Classic, split or block |
| LDAP server / Attribute / Filter | (ldapdropdown) LDAP query configuration |
| Root node | (dropdown Location) Root node of the tree |

---

## 7. Conditional Display

Conditions allow showing or hiding fields based on values entered elsewhere in the form.

### 7.1 Initial Field Visibility

| Mode | Description |
|------|-------------|
| Always visible | The field is always displayed |
| Hidden by default | The field is only shown if a condition is true |
| Visible by default | The field is shown unless a condition hides it |

### 7.2 Condition Configuration

| Parameter | Description |
|-----------|-------------|
| Controlled field | The field whose visibility is affected |
| Trigger field | The field whose value is evaluated |
| Check value | The value to compare against |
| Operator | EQ (=), NE (≠), LT (<), GT (>), LE (≤), GE (≥), REGEX, EMPTY, NOT EMPTY |
| Logic | AND / OR (to combine multiple conditions) |
| Order | Evaluation order for multiple conditions |

Field types that can act as triggers: `dropdown`, `dropdown_object`, `dropdown_meta`, `dropdown_multiple`, `dropdown_ldap`, `text`, `tel`, `email`, `url`, `checkbox`, `textarea`, `date`, `datetime`, `number`, `range`, `yesno`, `radio`.

---

## 8. Field Options (FieldOptions)

Field options allow attaching actions to specific values of a field (dropdown, checkbox, radio, yes/no).

For each field value, the following can be configured:

| Action | Description |
|--------|-------------|
| Linked task | Trigger a specific task when this value is selected |
| Show/hide a field | Control the visibility of another field |
| Show/hide a block | Control the visibility of an entire block |
| Validator | Assign a specific validator user when this value is selected |
| Child blocks | JSON array of blocks to show as children |

---

## 9. Tasks and Ticket Creation Workflow

### 9.1 Task Types

| Type | Description |
|------|-------------|
| **Ticket** | Create a child ticket in GLPI |
| **Sub-meta-demand** | Trigger another meta-demand |
| **Task** | Create a task on the parent ticket |
| **Email** | Send an email to configured recipients |

### 9.2 Ticket Task Configuration

Each ticket task is associated with a child ticket template (`TicketTask`) containing:

| Field | Description |
|-------|-------------|
| Category | ITIL category of the child ticket |
| Content | Child ticket description |
| Assigned technician | Default technician |
| Assigned group | Default group |
| Requester | Requester for the child ticket |
| Observer | Observer |
| Status | Initial status |
| Request type | Incident / Request |
| Formatted as table | Content is presented as an HTML table |

### 9.3 Block Control

Each task has a `block_use` JSON array listing the form blocks whose completion triggers this task. If the user did not fill any of those blocks, the task is not created.

### 9.4 Task Hierarchy

Tasks are organised in a tree (parent-child). The level (`level`) determines creation order:
- Level 1 tasks are created when the form is submitted.
- Next-level tasks are created when the parent ticket is resolved/closed (`addSonTickets()`).

### 9.5 Blocking Parent Ticket Closure

The `block_parent_ticket_resolution` option on a task prevents the parent ticket from being closed until that task/child ticket is resolved.

---

## 10. Submission Process (Wizard)

### Step 1 — Selection

The user sees the list of available meta-demands (or icon tile grid if `display_type=1`). Meta-demands are filtered by:
- User's active entity
- Group rights (if groups are configured on the meta-demand)
- Active status / maintenance mode

The list can show the most-used meta-demands ("top") and pinned forms. Text search is available.

### Step 2 — Form Filling

The user fills in the fields organised in numbered blocks and rows.

- Conditional fields show/hide dynamically via Ajax.
- Blocks can be hidden if no fields are visible (`hide_no_field=1`).
- The user can save a draft at any time (`use_draft=1`).

### Step 3 — Confirmation (optional)

If `use_confirm=1`, a summary page is displayed before final submission.

### Step 4 — Submission

1. The **parent ticket** is created with the configured template. The title is prefixed with `parent_ticket_tag`. The content includes a summary table of all field values.
2. If `validation_subticket=1`: a validation request is created. Child tickets are not created until the validator approves.
3. **Child tickets** are created according to the defined tasks and filled blocks.
4. If `create_pdf=1`: a PDF summary is generated and attached to the parent ticket.

### Automatic Redirect

If `plugin_metademands_on_login=1` (right): helpdesk interface users are redirected straight to the wizard on login.

If `simpleticket_to_metademand=1` (config): creating a standard ticket with a category linked to a meta-demand redirects to the wizard.

---

## 11. Step-by-Step Mode

### Principle

Step-by-step mode (`step_by_step_mode=1`) divides a form into sequential blocks filled by different groups. Each group receives a notification when it is their turn.

### Block Configuration

Path: **Step-by-step blocks** tab on the meta-demand

For each numbered block:
- **Assigned group**: group authorised to fill this block
- **Supervisor only**: restrict to the group supervisor
- **Reminder delay**: delay before sending a reminder notification
- **Message**: custom message for the notification

### Configstep Options

| Option | Description |
|--------|-------------|
| Show blocks as tabs | Display each block as a separate tab |
| Link user to block | Associate a user with their block |
| Multiple groups per block | Allow multiple group-block associations |
| Add user as requester | Add the user filling the block as a ticket requester |
| Supervisor validation | Require supervisor approval per step |
| Step-by-step interface | Restrict to: helpdesk (1), central (2), or both (0) |
| Allow option change | Allow changing step-by-step options at fill time |

### Flow

1. The form is submitted (parent ticket created).
2. A notification is sent to the group assigned to block 1.
3. Group 1 fills their block and validates.
4. A notification is sent to group 2, and so on.
5. Once all blocks are filled, child tickets are created.

---

## 12. Validation Before Child Ticket Creation

When `validation_subticket=1` on the meta-demand:

1. After submission, the parent ticket is created but child tickets are **not yet** created.
2. A `MetademandValidation` record is created with status **To validate**.
3. The designated validator sees a validation action in the ticket timeline.
4. **On approval**: child tickets are created.
5. **On refusal**: the meta-demand can be cancelled.

The validator can be:
- Configured globally on the meta-demand.
- Determined dynamically via a **FieldOption** (field value → specific validator).

---

## 13. Status Tracking

### Meta-demand Statuses (on the ticket)

| Status | Description |
|--------|-------------|
| **Running** | Some child tickets are still open |
| **To be closed** | All children are resolved, pending closure |
| **Closed** | Fully closed |

### Additional Search Fields on Tickets

| ID | Field | Description |
|----|-------|-------------|
| 9499 | Meta-demand approver | Validator user from MetademandValidation |
| 9500 | Meta-demand status | Running / To be closed / Closed |
| 9501 | Validation status | To validate / Tickets created / etc. |
| 9502 | Child ticket group | Group assigned to the child ticket |
| 9503 | Link to meta-demands | Number of linked child tickets |
| 9504 | Child ticket technician | Technician assigned to the child ticket |

### Automatic Action

| Action | Frequency | Description |
|--------|-----------|-------------|
| `MetademandsGlobalStatus` | Daily | Checks and updates the global status of all running meta-demands. Closes those whose children are all resolved. |

---

## 14. Notifications

### Inter-ticket followup

| Event | Description |
|-------|-------------|
| `add_interticketfollowup` | Fired when an inter-ticket followup is added (link between two tickets in the timeline) |

### Step-by-step forms

| Event | Description |
|-------|-------------|
| `new_step_form` | New form completed (step-by-step step) |
| `update_step_form` | Form updated (step-by-step step) |

### Available Template Variables (step-by-step)

| Variable | Description |
|----------|-------------|
| `##pluginmetademandsmetademand.title##` | Meta-demand name |
| `##pluginmetademandsstepform.date##` | Completion date |
| `##pluginmetademandsstepform.user_editor##` | User who filled the step |
| `##pluginmetademandsstepform.nextgroup##` | Next group to fill |
| `##pluginmetademandsstepform.users_id_dest##` | Destination user(s) |

### Dashboard Widgets (GLPI native)

Four count widgets available for GLPI dashboards:

| Widget | Description |
|--------|-------------|
| Running meta-demands | Count of meta-demands with "Running" status |
| Meta-demands to be closed | Count with "To be closed" status |
| Meta-demands to be validated | Count awaiting validation |
| Running (user's groups) | Running meta-demands filtered to the current user's groups |

---

## 15. Group Access Rights

Path: **Group rights** tab on the meta-demand

If groups are configured on a meta-demand, only users belonging to one of those groups can see and use that form in the wizard.

Without group restrictions, the form is accessible to all users with the `plugin_metademands_createmeta` right.

---

## 16. Predefined Ticket Fields

Path: **Ticket fields** tab on the meta-demand

Allows defining which GLPI fields for the parent ticket will be:
- **Predefined**: fixed pre-filled value
- **Mandatory**: the user must fill them in

These fields synchronise with GLPI ticket templates via the `tickettemplate` hook.

---

## 17. Export / Import

### XML Export

Path: **Export** tab on the meta-demand → `Tools > Import meta-demands`

Exports an entire meta-demand as an XML file, including:
- All fields and their parameters
- All tasks and ticket templates
- All conditions
- Field options
- General configuration

### XML Import

Path: `Configuration > Meta-Demands > Import meta-demands`

Imports a meta-demand from a previously exported XML file.

### Conversion from a GLPI 11 Form

The **Export** tab is also available on native GLPI 11 forms. It allows converting a GLPI 11 form into a meta-demand, mapping question types to the corresponding field types.

### Custom Values Bulk Import

Path: `front/importcustomvalues.php`

Bulk import of custom values for dropdown fields (`dropdown_meta`).

---

## 18. Basket Mode

Enabled via `is_basket=1` on the meta-demand.

### Concept

Basket mode allows users to order items from a reference catalogue with quantities. The `basket` field type in the form displays the catalogue and allows adding items.

### Reference Catalogue (Basketobject)

Path: `Management > Reference catalogue`

| Field | Description |
|-------|-------------|
| Name | Item name |
| Description | Description |
| Reference | Unique identifier |
| Type | Item category (Basketobjecttype) |

Item types (categories) are configurable in `Configuration > Dropdowns > Meta-Demands > Item types`.


---

## 19. Drafts

Enabled via `use_draft=1` in the configuration.

- Users can save a partially filled form.
- Drafts are private to the creating user.
- Accessible in **My meta-demands** → Drafts section.
- Two modes: standard form and basket mode.

---

## 20. Translations

### Meta-demand Translations

Path: **Translations** tab on the meta-demand

Name, comment, and description can be translated into multiple languages. The user's active GLPI language determines which translation is displayed.

### Field Translations

Each field can have its label and tooltip translated independently.

### Catalogue Item Translations

`Basketobject` and `Basketobjecttype` items also support translations.

---

## 21. Integration with Other Plugins

### Resources Plugin

- Link between a meta-demand and a Resources plugin **Contract Type**.
- When a resource record is viewed, the user is redirected to the meta-demand associated with their contract type.

### ServiceCatalog Plugin (legacy)

- Meta-demands appear in the ServiceCatalog widget.
- Title, comment and icon configurable in the plugin configuration.

### Fields Plugin

- A meta-demand field can be linked to a custom field created by the Fields plugin.
- Configured via the field's advanced parameters.

### DataInjection Plugin

- Import of catalogue items (Basketobject) via the DataInjection plugin.

### GLPI 11 — Native Service Catalogue

- Meta-demands appear in the GLPI 11 native service catalogue as form items.
- Filtering by category, entity, group access, and text search.

### GLPI 11 — Helpdesk Tile

- A configurable tile on the GLPI 11 helpdesk home page links to the meta-demands wizard.
- Title, description, illustration and translations configurable.

---

## 22. Inter-ticket Followup

Inter-ticket followup (`Interticketfollowup`) is a followup type that creates a link between two tickets in the timeline.

- Accessible from the ticket timeline (followup actions).
- Triggers a "New inter-ticket followup" notification.
- Requires the `plugin_metademands_followup` right.

---

## 23. Best Practices

- **Organise forms by ITIL category** so the redirect engine (`simpleticket_to_metademand`) can automatically direct users to the right form
- **Use conditional display** to simplify long forms: show only the relevant fields based on the user's choices
- **Define group rights** on sensitive forms to limit access to the relevant teams
- **Enable validation before child tickets** (`validation_subticket=1`) for processes requiring intermediate approval (changes, sensitive access)
- **Name tasks clearly** and configure `block_use` precisely to avoid creating irrelevant tickets
- **Use step-by-step mode** for processes involving several teams (onboarding, multi-department requests)
- **Enable drafts** for complex forms to prevent users from losing their input
- **Configure prefixes** (`parent_ticket_tag`, `son_ticket_tag`) to visually identify meta-demand-generated tickets in queues
- **Monitor the automatic action** `MetademandsGlobalStatus` in `Configuration > Automatic actions` to ensure it runs daily
- **Regularly export** important meta-demands to XML as a backup before any major modifications
