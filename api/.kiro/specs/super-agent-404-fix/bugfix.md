# Bugfix Requirements Document

## Introduction

Two admin panel routes — `GET /admin/super-agents/content-requests` and `GET /admin/super-agents/settings` — return 404 Not Found when navigated to in the browser. The bug is caused by a route ordering conflict in `routes/web.php`: the wildcard route `GET /super-agents/{id}` (registered for Super Agent detail pages) is defined before the specific literal routes for `content-requests` and `settings`. Laravel's router matches routes in registration order, so the `{id}` wildcard captures the literal path segments `content-requests` and `settings`, dispatching them to `SuperAgentManagementController@show` with a non-numeric ID. That method calls `User::findOrFail('content-requests')`, which finds no user and throws a ModelNotFoundException, producing the 404 response.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN an admin navigates to `GET /admin/super-agents/content-requests` THEN the system returns a 404 Not Found error instead of rendering the content bonus requests page.

1.2 WHEN an admin navigates to `GET /admin/super-agents/settings` THEN the system returns a 404 Not Found error instead of rendering the Super Agent settings page.

1.3 WHEN the router processes `GET /admin/super-agents/content-requests` THEN the system matches the wildcard route `GET /admin/super-agents/{id}` because it is registered earlier, passing the string `"content-requests"` as the `{id}` parameter to `SuperAgentManagementController@show`.

1.4 WHEN the router processes `GET /admin/super-agents/settings` THEN the system matches the wildcard route `GET /admin/super-agents/{id}` because it is registered earlier, passing the string `"settings"` as the `{id}` parameter to `SuperAgentManagementController@show`.

### Expected Behavior (Correct)

2.1 WHEN an admin navigates to `GET /admin/super-agents/content-requests` THEN the system SHALL dispatch the request to `ContentBonusController@index` and render the content bonus requests view.

2.2 WHEN an admin navigates to `GET /admin/super-agents/settings` THEN the system SHALL dispatch the request to `SuperAgentSettingsController@index` and render the Super Agent settings view.

2.3 WHEN the router processes `GET /admin/super-agents/content-requests` THEN the system SHALL match the specific literal route registered for that path, not the wildcard `{id}` route.

2.4 WHEN the router processes `GET /admin/super-agents/settings` THEN the system SHALL match the specific literal route registered for that path, not the wildcard `{id}` route.

### Unchanged Behavior (Regression Prevention)

3.1 WHEN an admin navigates to `GET /admin/super-agents/{id}` with a valid numeric user ID THEN the system SHALL CONTINUE TO dispatch the request to `SuperAgentManagementController@show` and render the Super Agent detail page.

3.2 WHEN an admin submits `POST /admin/super-agents/content-requests/{id}/approve` THEN the system SHALL CONTINUE TO dispatch the request to `ContentBonusController@approve`.

3.3 WHEN an admin submits `POST /admin/super-agents/content-requests/{id}/reject` THEN the system SHALL CONTINUE TO dispatch the request to `ContentBonusController@reject`.

3.4 WHEN an admin submits `POST /admin/super-agents/settings` THEN the system SHALL CONTINUE TO dispatch the request to `SuperAgentSettingsController@update`.

3.5 WHEN an admin navigates to `GET /admin/super-agents/applications` THEN the system SHALL CONTINUE TO dispatch the request to `SuperAgentApplicationController@index`.

3.6 WHEN an admin accesses any other existing Super Agent admin route THEN the system SHALL CONTINUE TO route correctly without regression.

---

## Bug Condition (Structured Pseudocode)

**Bug Condition Function** — identifies requests that trigger the 404:

```pascal
FUNCTION isBugCondition(request)
  INPUT: request of type HttpRequest
  OUTPUT: boolean

  // The bug triggers when a GET request targets a literal sub-path under
  // /admin/super-agents/ that is registered *after* the {id} wildcard route
  RETURN request.method = "GET"
     AND (request.path = "/admin/super-agents/content-requests"
          OR request.path = "/admin/super-agents/settings")
END FUNCTION
```

**Fix Checking Property:**

```pascal
// Property: Fix Checking — affected routes must resolve correctly after the fix
FOR ALL request WHERE isBugCondition(request) DO
  response ← router.dispatch'(request)
  ASSERT response.status != 404
  ASSERT response.matchedController IN [ContentBonusController, SuperAgentSettingsController]
END FOR
```

**Preservation Checking Property:**

```pascal
// Property: Preservation Checking — all other routes must behave identically before and after fix
FOR ALL request WHERE NOT isBugCondition(request) DO
  ASSERT router.dispatch(request) = router.dispatch'(request)
END FOR
```
