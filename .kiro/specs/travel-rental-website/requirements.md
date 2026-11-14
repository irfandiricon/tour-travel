# Requirements Document

## Introduction

The Travel Rental Website is a public-facing travel and car rental web application built with CodeIgniter 4.7.x on PHP 8.5.x. The product consists of two surfaces: a single public Landing Page that presents the rental business (fleet, information, news, gallery, FAQ, and contact), and a CMS Admin that lets administrators author Information and News content, configure Landing Page content, and manage system configuration.

The Landing Page supports three languages — English (default), Indonesian, and Malay — with content served in the visitor's selected language. Bookings are not processed inside the application; instead, visitors are directed to a configurable WhatsApp number, where the business completes the booking conversation. The WhatsApp number is managed through the CMS Admin configuration.

The application follows the workspace CodeIgniter 4 patterns: public page controllers and Admin controllers extend `BaseController`, while `Api*Controller` classes extend `Controller` and act as a proxy layer that calls `api/v1/*` endpoints via `Service::ApiRequestWithHeaderToken()`.

## Glossary

- **Landing_Page**: The single public-facing page that presents the rental business content to visitors.
- **CMS_Admin**: The authenticated administrative interface used to manage content, configuration, and the Landing Page.
- **Content_Manager**: An authenticated administrator who signs in to the CMS_Admin to manage content and configuration.
- **Visitor**: An unauthenticated public user who browses the Landing_Page.
- **Information_Item**: A static informational content record (for example, About, Terms, Service descriptions) authored in the CMS_Admin and displayed on the Landing_Page.
- **News_Item**: A time-ordered article record authored in the CMS_Admin and displayed on the Landing_Page.
- **Fleet_Item**: A vehicle record (car, bus, or other vehicle) displayed in the Landing_Page fleet section.
- **FAQ_Item**: A question-and-answer record displayed on the Landing_Page.
- **Supported_Language**: One of the three languages the system serves content in: English (`en`), Indonesian (`id`), Malay (`ms`).
- **Default_Language**: English (`en`), the language served when no language is selected or a requested translation is unavailable.
- **Language_Selector**: The Landing_Page control that lets a Visitor choose a Supported_Language.
- **WhatsApp_Number**: The configurable phone number, managed in the CMS_Admin, used to start a booking conversation on WhatsApp.
- **WhatsApp_Booking_Link**: A WhatsApp click-to-chat URL generated from the configured WhatsApp_Number.
- **Site_Configuration**: The collection of configurable Landing_Page settings managed in the CMS_Admin (for example, WhatsApp_Number, contact details, section content).
- **Page_Controller**: A public controller that extends `BaseController` and renders Landing_Page views.
- **Admin_Controller**: An authenticated CMS_Admin controller that extends `BaseController`.
- **Api_Controller**: An `Api*Controller` that extends `Controller` and proxies requests to `api/v1/*` endpoints via `Service::ApiRequestWithHeaderToken()`.
- **Vendor_Assets**: The third-party front-end libraries (Bootstrap 5.3, Bootstrap Icons, Chart.js) stored locally under `public/assets/vendor`.
- **Public_Layout**: The shared Bootstrap view layout used by the Landing_Page.
- **Admin_Layout**: The shared Bootstrap view layout used by all CMS_Admin pages, with a sidebar that collapses into an offcanvas panel on small screens.
- **Sign_In_Page**: The CMS_Admin sign-in form.
- **Hero_Carousel**: The Bootstrap carousel at the top of the Landing_Page that cycles through slides built from published gallery images and the hero content.
- **Responsive_Range**: Viewport widths from 320 pixels up to and including desktop widths of 1920 pixels.
- **SEO_Meta_Builder**: A pure library (`SeoMetaBuilder`) that builds the title, description, canonical URL, hreflang alternates, Open Graph tags, Twitter Card tags, and JSON-LD structured data from the Landing_Page view-model and Site_Configuration.
- **SEO_Settings**: The Site_Configuration fields for site name, meta description, meta keywords (each in en, id, ms), and the default Open Graph image.
- **Robots_Endpoint**: The public `/robots.txt` route.
- **Sitemap_Endpoint**: The public `/sitemap.xml` route.
- **Visitor_Tracker**: The server-side component that records Landing_Page visits into the `visitor_logs` table.
- **Visitor_Hash**: A SHA-256 hash of the client IP address, User-Agent, visit date, and an application-key salt, produced by the `VisitorHasher` helper.
- **Device_Detector**: A pure library (`DeviceDetector`) that classifies a User-Agent string as desktop, mobile, tablet, or bot.
- **Booking_Click_Endpoint**: The public `POST /track/booking` route that records a WhatsApp booking click in the `site_events` table.
- **Dashboard_Stats**: A pure library (`DashboardStats`) that groups, zero-fills, and computes percentages for dashboard metrics.
- **Dashboard**: The CMS_Admin landing page after sign-in, showing visitor analytics and content summary information.
- **Dashboard_Summary_Endpoint**: The `api/v1/dashboard/summary` endpoint that returns all Dashboard metrics.
- **Unique_Visitor**: A distinct Visitor_Hash counted once within a given time window, excluding bots.

## Requirements

### Requirement 1: Landing Page Display

**User Story:** As a Visitor, I want to view the rental business landing page, so that I can learn about the fleet, services, and how to book.

#### Acceptance Criteria

1. WHEN a Visitor requests the Landing_Page, THE Page_Controller SHALL render the Landing_Page with the hero, about, fleet, gallery, FAQ, and contact sections, each populated from Site_Configuration and published content, within 3 seconds under normal load.
2. THE Page_Controller SHALL render Landing_Page content in the Supported_Language currently selected by the Visitor, where Supported_Language is one of EN, ID, or MS.
3. WHEN no Supported_Language has been selected by the Visitor, THE Page_Controller SHALL render the Landing_Page in the Default_Language (EN).
4. WHEN a Visitor requests the Landing_Page, THE Page_Controller SHALL display only Information_Items, News_Items, and Fleet_Items whose status is published, and SHALL exclude all items with any other status.
5. WHERE a Fleet_Item has an associated image, THE Page_Controller SHALL display the image in the fleet section.
6. IF a Fleet_Item has no associated image, THEN THE Page_Controller SHALL display a placeholder image in the fleet section and SHALL continue rendering remaining Fleet_Items.
7. IF Site_Configuration cannot be retrieved or returns empty for a required section, THEN THE Page_Controller SHALL render the Landing_Page with that section omitted and SHALL display an indication that the section content is unavailable, without failing the overall page render.
8. WHEN a Visitor requests the Landing_Page and no published Information_Items, News_Items, or Fleet_Items exist for a section, THE Page_Controller SHALL render that section with an empty-state indication and SHALL render all remaining populated sections.

### Requirement 2: Multi-Language Support

**User Story:** As a Visitor, I want to switch the website language between English, Indonesian, and Malay, so that I can read content in my preferred language.

#### Acceptance Criteria

1. THE Landing_Page SHALL provide a Language_Selector offering exactly three Supported_Language options: English (en), Indonesian (id), and Malay (ms).
2. WHEN a Visitor loads the Landing_Page with no previously persisted language selection in the current session, THE Page_Controller SHALL render the Landing_Page in the Default_Language (en).
3. WHEN a Visitor selects a Supported_Language from the Language_Selector, THE Page_Controller SHALL render the Landing_Page in the selected Supported_Language.
4. WHEN a Visitor selects a Supported_Language from the Language_Selector, THE Page_Controller SHALL persist the selection for the current session so that subsequent requests in the same session render in the selected Supported_Language without re-selection.
5. IF a request specifies a language value that is not one of the Supported_Language values (en, id, ms), THEN THE Page_Controller SHALL render the Landing_Page in the Default_Language (en) and SHALL NOT change any persisted language selection.
6. IF a requested content field has no translation in the selected Supported_Language, THEN THE Page_Controller SHALL display the Default_Language (en) value for that field.
7. IF a requested content field has no translation in the selected Supported_Language AND no Default_Language (en) value exists, THEN THE Page_Controller SHALL display an empty string for that field and SHALL render the remaining fields of the Landing_Page without error.
8. THE CMS_Admin SHALL allow a Content_Manager to enter a value, with a maximum length of 5000 characters per field, for each translatable content field in English (en), Indonesian (id), and Malay (ms).
9. IF a Content_Manager saves a content record without an English (en) value for a translatable field, THEN THE Admin_Controller SHALL reject the save, retain the previously stored record unchanged, and return a validation message identifying the missing Default_Language field.

### Requirement 3: CMS Admin Authentication

**User Story:** As a Content_Manager, I want to sign in to the CMS Admin, so that only authorized administrators can manage content and configuration.

#### Acceptance Criteria

1. WHEN an unauthenticated user requests any CMS_Admin page other than the sign-in page, THE Admin_Controller SHALL redirect the user to the CMS_Admin sign-in page without exposing the requested page content.
2. WHEN a user submits credentials that match an active Content_Manager account to the CMS_Admin sign-in page, THE Admin_Controller SHALL establish an authenticated session that expires after 30 minutes of inactivity and redirect the user to the CMS_Admin dashboard.
3. IF a user submits the CMS_Admin sign-in form with a username or password that is empty or does not match an active Content_Manager account, THEN THE Admin_Controller SHALL reject the sign-in attempt, leave the session unauthenticated, and display an authentication error message that does not disclose which field was incorrect.
4. WHEN an authenticated Content_Manager requests to sign out, THE Admin_Controller SHALL terminate the authenticated session and redirect the user to the CMS_Admin sign-in page.
5. IF a user submits 5 or more consecutive invalid sign-in attempts for the same username within 15 minutes, THEN THE Admin_Controller SHALL reject further sign-in attempts for that username for 15 minutes and display a message indicating the account is temporarily locked.
6. WHILE an authenticated session has been inactive for 30 minutes or more, THE Admin_Controller SHALL treat the session as unauthenticated and require the Content_Manager to sign in again.

### Requirement 4: Information Content Management

**User Story:** As a Content_Manager, I want to create, edit, and remove Information content, so that the Landing Page presents accurate business information.

#### Acceptance Criteria

1. WHEN a Content_Manager submits a new Information_Item with all required fields present and each translatable text field (en, id, ms) containing 1 to 5000 characters, THE Api_Controller SHALL create the Information_Item and return a success response with status 200 and the created Information_Item identifier.
2. WHEN a Content_Manager requests the list of Information_Items, THE Api_Controller SHALL return the Information_Items with pagination metadata including the total count, the current page number, and a page size between 1 and 100 items per page defaulting to 10.
3. WHEN a Content_Manager submits edits to an existing Information_Item with all required fields present and each translatable text field (en, id, ms) containing 1 to 5000 characters, THE Api_Controller SHALL update the Information_Item and return a success response with status 200.
4. WHEN a Content_Manager requests deletion of an existing Information_Item, THE Api_Controller SHALL remove the Information_Item and return a success response with status 200.
5. IF a Content_Manager submits an Information_Item with one or more missing required fields, THEN THE Api_Controller SHALL reject the request without persisting any changes and return a validation error with status 422 identifying each missing field by name.
6. IF a Content_Manager submits a create or edit request referencing an Information_Item identifier that does not exist, THEN THE Api_Controller SHALL reject the request without persisting any changes and return an error response with status 404 indicating the Information_Item was not found.
7. IF the Api_Controller's request to api/v1/* fails or returns no response, THEN THE Api_Controller SHALL return an error response with a non-200 status and a message indicating the upstream request failed, without reporting the operation as successful.
8. WHEN a Content_Manager sets an Information_Item status to published, THE Api_Controller SHALL mark the Information_Item as visible on the Landing_Page in all three translations (en, id, ms) and return a success response with status 200.

### Requirement 5: News Content Management

**User Story:** As a Content_Manager, I want to create, edit, and remove News content, so that visitors can read current announcements and articles.

#### Acceptance Criteria

1. WHEN a Content_Manager submits a new News_Item with a title of 1 to 200 characters and a body of 1 to 20000 characters in each of the three translations (en, id, ms), THE Api_Controller SHALL create the News_Item with a publish date and return a success response with status 200.
2. WHEN a Content_Manager requests the list of News_Items, THE Api_Controller SHALL return the News_Items ordered by publish date in descending order with pagination metadata including the total count, the current page number (minimum 1), and a page size between 1 and 100 defaulting to 10.
3. WHEN a Content_Manager submits edits to an existing News_Item with a title of 1 to 200 characters and a body of 1 to 20000 characters in each of the three translations (en, id, ms), THE Api_Controller SHALL update the News_Item and return a success response with status 200.
4. WHEN a Content_Manager requests deletion of an existing News_Item, THE Api_Controller SHALL remove the News_Item and return a success response with status 200.
5. IF a Content_Manager submits a News_Item with one or more missing or oversized required fields, THEN THE Api_Controller SHALL reject the request without persisting any changes and return a validation error with status 422 identifying each invalid field by name.
6. IF a Content_Manager submits an edit or delete request referencing a News_Item identifier that does not exist, THEN THE Api_Controller SHALL reject the request without persisting any changes and return an error response with status 404 indicating the News_Item was not found.
7. IF the Api_Controller's request to api/v1/* fails or returns no response, THEN THE Api_Controller SHALL return an error response with a non-200 status and a message indicating the upstream request failed, without reporting the operation as successful.
8. WHEN a Visitor requests the Landing_Page, THE Page_Controller SHALL display published News_Items (those with a publish date on or before the current timestamp) ordered by publish date in descending order.
9. WHEN a Visitor requests the Landing_Page and no published News_Items exist, THE Page_Controller SHALL render the news section with an empty-state indication.

### Requirement 6: Landing Page Content Configuration

**User Story:** As a Content_Manager, I want to configure the Landing Page sections and fleet, so that I can control what visitors see without code changes.

#### Acceptance Criteria

1. WHEN a Content_Manager saves Site_Configuration with all required fields present and valid, THE Api_Controller SHALL persist the Site_Configuration and return a success response within 5 seconds.
2. IF a Content_Manager saves Site_Configuration with one or more required fields missing or invalid, THEN THE Api_Controller SHALL reject the request, retain the previously persisted Site_Configuration unchanged, and return a validation error identifying each invalid field.
3. WHEN a Content_Manager updates the hero, about, gallery, or contact section content, THE Api_Controller SHALL persist the updated section content for each of the three Supported_Languages (en, id, ms).
4. IF a Content_Manager updates section content for fewer than all three Supported_Languages (en, id, ms), THEN THE Api_Controller SHALL reject the request and return a validation error identifying each Supported_Language with missing content.
5. WHEN a Content_Manager submits a new Fleet_Item with all required fields present and valid, THE Api_Controller SHALL create the Fleet_Item and return a success response within 5 seconds.
6. WHEN a Content_Manager submits a new FAQ_Item with all required fields present and valid, THE Api_Controller SHALL create the FAQ_Item and return a success response within 5 seconds.
7. WHEN a Content_Manager uploads an image of an allowed image type (JPEG, PNG, or WebP) and size not exceeding 5 megabytes for a Fleet_Item or gallery, THE Api_Controller SHALL store the image and associate the stored image reference with the record.
8. IF a Content_Manager uploads a file whose type is not an allowed image type (JPEG, PNG, or WebP), THEN THE Api_Controller SHALL reject the upload, retain the existing record unchanged, and return a validation error identifying the allowed image types.
9. IF a Content_Manager uploads an image file exceeding 5 megabytes, THEN THE Api_Controller SHALL reject the upload, retain the existing record unchanged, and return a validation error identifying the maximum allowed size of 5 megabytes.

### Requirement 7: WhatsApp Booking Configuration and Flow

**User Story:** As a Content_Manager, I want to configure the WhatsApp number used for bookings, so that visitor booking requests reach the correct business contact.

#### Acceptance Criteria

1. THE CMS_Admin SHALL provide a configuration field for the WhatsApp_Number within Site_Configuration that accepts 8 to 15 digits in international E.164 format with a leading plus sign and country code.
2. WHEN a Content_Manager saves a WhatsApp_Number that contains 8 to 15 digits in E.164 international format, THE Api_Controller SHALL persist the WhatsApp_Number and return a success response indicating the save succeeded.
3. IF a Content_Manager saves a WhatsApp_Number that does not match E.164 international format (missing country code, contains non-digit characters other than a single leading plus sign, or has fewer than 8 or more than 15 digits), THEN THE Api_Controller SHALL reject the save, retain the previously stored WhatsApp_Number unchanged, and return a validation error indicating the required E.164 international format.
4. WHEN a Content_Manager saves Site_Configuration with the WhatsApp_Number field empty, THE Api_Controller SHALL persist an empty WhatsApp_Number and return a success response indicating booking is disabled.
5. WHEN a Visitor activates a booking action on the Landing_Page and a valid WhatsApp_Number is configured, THE Page_Controller SHALL open a WhatsApp_Booking_Link (wa.me click-to-chat) generated from the configured WhatsApp_Number within 2 seconds.
6. WHERE a Fleet_Item booking action is activated, THE Page_Controller SHALL include the Fleet_Item name, limited to a maximum of 200 characters, in the prefilled message of the WhatsApp_Booking_Link.
7. IF no WhatsApp_Number is configured in Site_Configuration or the stored WhatsApp_Number is empty, THEN THE Page_Controller SHALL hide the booking action from the Landing_Page.

### Requirement 8: FAQ Display and Management

**User Story:** As a Content_Manager, I want to manage FAQ entries, so that visitors can find quick answers to common questions.

#### Acceptance Criteria

1. WHEN a Content_Manager submits a new FAQ_Item with a question of 1 to 500 characters and an answer of 1 to 5000 characters in each Supported_Language (en, id, ms), THE Api_Controller SHALL create the FAQ_Item and return a success response with status 200.
2. WHEN a Content_Manager requests deletion of an existing FAQ_Item, THE Api_Controller SHALL remove the FAQ_Item and return a success response with status 200.
3. IF a Content_Manager requests deletion of a FAQ_Item identifier that does not exist, THEN THE Api_Controller SHALL return an error response with status 404 indicating the FAQ_Item was not found.
4. WHEN a Visitor requests the Landing_Page, THE Page_Controller SHALL display published FAQ_Items in the selected Supported_Language, falling back to the Default_Language (en) value for any field without a translation.
5. WHEN a Visitor requests the Landing_Page and no published FAQ_Items exist, THE Page_Controller SHALL render the FAQ section with an empty-state indication.
6. IF a Content_Manager submits a FAQ_Item with an empty question or empty answer in the Default_Language (en), THEN THE Api_Controller SHALL reject the request without persisting any changes and return a validation error identifying the missing field.

### Requirement 9: Architectural Conformance

**User Story:** As a developer, I want the implementation to follow the workspace CodeIgniter 4 patterns, so that the codebase remains consistent and maintainable.

#### Acceptance Criteria

1. THE Api_Controller classes SHALL extend `Controller` and SHALL NOT extend `BaseController`, and SHALL proxy content operations to `api/v1/*` endpoints via `Service::ApiRequestWithHeaderToken()`.
2. THE Page_Controller and Admin_Controller classes SHALL extend `BaseController`.
3. WHEN an Api_Controller method is invoked via AJAX, THE Api_Controller SHALL return a JSON response containing status, message, data, and total fields.
4. WHEN an Api_Controller method is invoked from a PHP Admin_Controller (not via AJAX), THE Api_Controller SHALL return an array.
5. IF an Api_Controller's proxied request via `Service::ApiRequestWithHeaderToken()` fails or returns a null/invalid response, THEN THE Api_Controller SHALL apply null-safe fallbacks and return a response with a non-200 status and an error message rather than throwing an unhandled error.
6. THE Api_Controller and Admin_Controller classes SHALL declare every property that is assigned in the constructor.
7. THE Admin_Controller classes SHALL NOT duplicate authCheck, menu, service, or user initialization already provided by `BaseController::initController()`.

### Requirement 10: Bootstrap Responsive User Interface

**User Story:** As a Visitor and as a Content_Manager, I want a polished, responsive interface on the Landing_Page, the CMS_Admin, and the Sign_In_Page, so that I can use the application comfortably on any device.

#### Acceptance Criteria

1. THE Public_Layout, THE Admin_Layout, and THE Sign_In_Page SHALL use Bootstrap 5.3 and Bootstrap Icons served from Vendor_Assets under `public/assets/vendor`.
2. THE Public_Layout, THE Admin_Layout, and THE Sign_In_Page SHALL load all CSS and JavaScript from local Vendor_Assets and SHALL NOT reference a third-party CDN at runtime.
3. THE Public_Layout, THE Admin_Layout, and THE Sign_In_Page SHALL render without horizontal scrolling at every viewport width in the Responsive_Range.
4. WHILE the viewport width is below 992 pixels, THE Public_Layout SHALL collapse the navigation bar into a toggle button that expands and collapses the navigation links.
5. WHILE the viewport width is below 992 pixels, THE Admin_Layout SHALL present the sidebar as an offcanvas panel opened by a toggle button.
6. WHEN the Landing_Page is rendered and at least one published gallery image exists, THE Hero_Carousel SHALL render one slide per published gallery image, up to a maximum of 5 slides, ordered by gallery sort order.
7. WHEN the Landing_Page is rendered and no published gallery image exists, THE Page_Controller SHALL render a single static hero section in place of the Hero_Carousel.
8. WHERE the Hero_Carousel has more than one slide, THE Hero_Carousel SHALL autoplay with an interval of 5000 milliseconds, SHALL pause while the pointer hovers over the carousel or a control has keyboard focus, and SHALL display previous/next controls and slide indicators.
9. WHERE the Hero_Carousel has more than one slide, THE Hero_Carousel SHALL respond to the left and right arrow keys when it has keyboard focus.
10. WHILE the Visitor's browser reports the `prefers-reduced-motion: reduce` setting, THE Hero_Carousel SHALL disable autoplay and slide transition animation.
11. THE Hero_Carousel SHALL display the hero title and hero subtitle in the selected Supported_Language over every slide.
12. THE Landing_Page fleet section SHALL present each published Fleet_Item as a Bootstrap card within a responsive grid that shows 1 column below 576 pixels, 2 columns from 576 to 991 pixels, and 3 columns from 992 pixels.
13. THE Landing_Page FAQ section SHALL present FAQ_Items as a Bootstrap accordion.
14. THE Public_Layout SHALL provide a Language_Selector as a dropdown that offers exactly the three Supported_Language links en, id, and ms.
15. WHERE a valid WhatsApp_Booking_Link exists, THE Landing_Page SHALL display a floating WhatsApp button that opens the WhatsApp_Booking_Link; otherwise THE Landing_Page SHALL hide the floating WhatsApp button.
16. THE Admin_Layout SHALL render list tables inside a responsive table wrapper that scrolls horizontally within the wrapper on viewports narrower than the table.
17. IF an admin form submission returns a 422 validation response, THEN THE Admin_Layout form SHALL display each error beside the corresponding field using Bootstrap validation feedback styling.
18. WHEN an admin operation completes or fails, THE Admin_Layout SHALL display the result message in a Bootstrap toast.
19. THE Public_Layout, THE Admin_Layout, and THE Sign_In_Page SHALL associate every form control with a visible label, SHALL show a visible focus indicator on every interactive element, and SHALL provide an `aria-label` or `aria-labelledby` on every icon-only control.
20. THE Public_Layout, THE Admin_Layout, and THE Sign_In_Page SHALL render text with a contrast ratio of at least 4.5:1 against its background.
21. THE redesigned views SHALL preserve the existing view-model keys, element ids, routes, `admin/api/<slug>/*` endpoints, CSRF handling, inline 422 error rendering, and EN/ID/MS tab behavior that the existing automated tests and `admin.js` depend on.

### Requirement 11: Search Engine Optimization

**User Story:** As the business owner, I want the Landing_Page to be well optimized for search engines and social sharing, so that more prospective customers find the rental business.

#### Acceptance Criteria

1. WHEN a Visitor requests the Landing_Page, THE SEO_Meta_Builder SHALL produce a title, a meta description, a canonical URL, and a robots directive of `index, follow` in the selected Supported_Language.
2. THE SEO_Meta_Builder SHALL truncate the meta description to at most 160 characters.
3. WHERE a meta keywords value exists for the selected Supported_Language, THE SEO_Meta_Builder SHALL include a meta keywords tag; otherwise THE SEO_Meta_Builder SHALL omit the tag.
4. THE SEO_Meta_Builder SHALL produce one hreflang alternate link for each Supported_Language (en, id, ms) plus one `x-default` alternate pointing to the Default_Language URL.
5. THE SEO_Meta_Builder SHALL produce absolute, well-formed URLs for the canonical link and for every hreflang alternate.
6. THE SEO_Meta_Builder SHALL produce Open Graph tags `og:title`, `og:description`, `og:type`, `og:url`, `og:image`, and `og:locale`, and Twitter Card tags `twitter:card`, `twitter:title`, `twitter:description`, and `twitter:image`.
7. WHEN at least one published gallery image or hero image exists, THE SEO_Meta_Builder SHALL use the first such image, as an absolute URL, for `og:image`; otherwise THE SEO_Meta_Builder SHALL use the default Open Graph image from SEO_Settings, and, if none is configured, a bundled default image.
8. THE SEO_Meta_Builder SHALL produce JSON-LD structured data of type `AutoRental` (a `LocalBusiness` subtype) containing the site name, URL, description, image, and, when configured, the contact email, address, and telephone number.
9. WHERE at least one published FAQ_Item exists, THE SEO_Meta_Builder SHALL add a JSON-LD `FAQPage` entry containing one question and answer pair per published FAQ_Item in the selected Supported_Language.
10. THE SEO_Meta_Builder SHALL produce JSON-LD output that parses as valid JSON.
11. THE Landing_Page SHALL contain exactly one `h1` element, SHALL use the semantic landmarks `header`, `nav`, `main`, and `footer`, and SHALL set the `lang` attribute of the `html` element to the selected Supported_Language.
12. THE Landing_Page SHALL render an `alt` attribute on every `img` element, using the localized item name or caption and an empty `alt` only for purely decorative images.
13. THE Landing_Page SHALL set `loading="lazy"` on every `img` element located outside the first carousel slide.
14. WHEN a client requests the Robots_Endpoint, THE Page_Controller SHALL return a `text/plain` response that allows crawling of the Landing_Page, disallows `/admin`, `/signin`, `/api`, and `/dashboard`, and references the absolute URL of the Sitemap_Endpoint.
15. WHEN a client requests the Sitemap_Endpoint, THE Page_Controller SHALL return an `application/xml` response that lists the absolute Landing_Page URL with `xhtml:link` alternates for en, id, and ms.
16. THE Sign_In_Page and every CMS_Admin page SHALL include the robots directive `noindex, nofollow`.
17. THE CMS_Admin Site_Configuration form SHALL provide SEO_Settings fields for site name, meta description, and meta keywords in each of en, id, and ms, and for the default Open Graph image by upload or path.
18. WHEN a Content_Manager saves SEO_Settings that include a meta description in any Supported_Language, THE Api_Controller SHALL require the English (en) meta description and SHALL limit each meta description to 320 characters and each meta keywords value to 255 characters.
19. IF a Content_Manager saves SEO_Settings that violate a rule in criterion 18, THEN THE Api_Controller SHALL reject the save, retain the previously stored SEO_Settings unchanged, and return a 422 validation error naming each invalid field.
20. IF a Content_Manager uploads a default Open Graph image that is not a JPEG, PNG, or WebP file or that exceeds 5 megabytes, THEN THE Api_Controller SHALL reject the upload and return a validation error identifying the allowed types or the 5 megabyte maximum.
21. THE database migration that adds SEO_Settings columns to `site_config` SHALL be a new migration, SHALL be idempotent when run repeatedly, and SHALL be mirrored in `database/schema.sql`.

### Requirement 12: Visitor Analytics and Admin Dashboard

**User Story:** As a Content_Manager, I want a dashboard showing visitor statistics and content summaries, so that I can understand site traffic, booking interest, and the state of my content.

#### Acceptance Criteria

1. WHEN a Visitor requests the Landing_Page, THE Visitor_Tracker SHALL insert one row into `visitor_logs` containing visit_date, visitor_hash, page, lang, device_type, referrer_host, is_bot, and created_at, with is_bot set to true when the Device_Detector classifies the request as bot.
2. THE Visitor_Hash SHALL be the SHA-256 hash of the client IP address, the User-Agent, the visit date, and a salt derived from the application key.
3. THE Visitor_Tracker SHALL NOT store the raw client IP address in any database column or log message.
4. THE Visitor_Hash SHALL remain identical for repeated requests with the same IP address, User-Agent, and visit date, and SHALL differ for requests on different visit dates.
5. THE Device_Detector SHALL classify every User-Agent string, including empty strings, as exactly one of desktop, mobile, tablet, or bot.
6. WHEN the Device_Detector classifies a request as bot, THE Visitor_Tracker SHALL exclude the request from all visitor counts, page view counts, language distribution, device distribution, and referrer metrics.
7. THE Visitor_Tracker SHALL NOT record requests to `/admin`, `/api`, `/signin`, `/dashboard`, `/track`, `/robots.txt`, `/sitemap.xml`, or static asset paths.
8. IF the Visitor_Tracker fails to write to `visitor_logs`, THEN THE Page_Controller SHALL log the error without the raw IP address and SHALL still render the Landing_Page with status 200.
9. WHEN a Visitor activates a WhatsApp booking action, THE Landing_Page SHALL send a `POST /track/booking` request containing the fleet id, the fleet name, and the selected Supported_Language without delaying the opening of the WhatsApp_Booking_Link.
10. WHEN the Booking_Click_Endpoint receives a valid payload, THE Booking_Click_Endpoint SHALL insert a row into `site_events` with event_type `booking_click`, ref_id, ref_label (truncated to 200 characters), lang, visitor_hash, and created_at, and SHALL return status 200.
11. IF the Booking_Click_Endpoint receives a payload with a missing fleet id, a non-numeric fleet id, a fleet name longer than 200 characters, or a language outside the Supported_Language set, THEN THE Booking_Click_Endpoint SHALL reject the request with status 422 and SHALL NOT insert a row.
12. WHEN a single visitor_hash sends more than 10 requests to the Booking_Click_Endpoint within 60 seconds, THE Booking_Click_Endpoint SHALL reject the additional requests with status 429 and SHALL NOT insert rows for them.
13. THE Booking_Click_Endpoint SHALL NOT record events from requests classified as bot by the Device_Detector.
14. THE Dashboard_Summary_Endpoint SHALL require an authenticated admin session or a signed internal proxy call and SHALL return status 401 or 403 otherwise.
15. WHEN a Content_Manager opens the Dashboard, THE Admin_Controller SHALL obtain all Dashboard metrics through an Api_Controller that proxies to the Dashboard_Summary_Endpoint.
16. THE Dashboard SHALL display Bootstrap cards for total visitors (all time), Unique_Visitors today, Unique_Visitors in the last 7 days, Unique_Visitors in the last 30 days, and total page views.
17. THE Dashboard SHALL display a Chart.js line or bar chart of page views and Unique_Visitors per day for the last 30 days, with one entry for each of the 30 days ending today, including days with zero visits.
18. THE Dashboard SHALL display the language distribution (en, id, ms) and the device distribution (desktop, mobile, tablet) as charts with percentages.
19. THE Dashboard_Stats SHALL compute each distribution so that the percentages sum to 100 when the total count is greater than 0 and every percentage equals 0 when the total count is 0.
20. THE Dashboard SHALL display the top 5 referrer hosts by visit count.
21. THE Dashboard SHALL display the total WhatsApp booking clicks, the clicks in the last 7 days, the clicks in the last 30 days, and the top 5 Fleet_Items by booking clicks.
22. THE Dashboard SHALL display the count of published and draft records for Fleet_Items, News_Items, Information_Items, FAQ_Items, and gallery items.
23. THE Dashboard SHALL display the 5 most recent News_Items and the most recent admin sign-in time.
24. IF no WhatsApp_Number is configured in Site_Configuration, THEN THE Dashboard SHALL display a warning card that links to the Site_Configuration page.
25. WHEN no visitor data exists, THE Dashboard SHALL display zero values and an empty-state message in each chart without failing to render.
26. THE Dashboard SHALL load Chart.js from Vendor_Assets and SHALL render all charts without a runtime CDN request.
27. THE database migrations that create `visitor_logs` and `site_events` SHALL be new migrations, SHALL be idempotent when run repeatedly, SHALL create indexes on `visitor_logs.visit_date` and `visitor_logs.visitor_hash`, and SHALL be mirrored in `database/schema.sql`.
28. THE `DeviceDetector`, `VisitorHasher`, `DashboardStats`, and `SeoMetaBuilder` classes SHALL be implemented as pure classes without database or HTTP access so that automated tests can exercise them directly.
