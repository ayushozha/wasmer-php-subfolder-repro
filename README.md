# PHP app in a subfolder fails to deploy from GitHub on Wasmer

A PHP site with a Composer dependency lives in `app/`. Deploying this repo from GitHub with the root directory set
to `app` fails at the build step. Uploading the same `app/` folder as a ZIP deploys and serves the site.

| How it's deployed | Result |
| --- | --- |
| GitHub, root directory `app` | ❌ Build fails: `"/composer.json": not found` |
| ZIP upload of the `app/` folder | ✅ Deploys, the site returns HTTP 200 |

Reproduced on production on Nov 12, 2026.

## Steps to reproduce

1. In the Wasmer dashboard, choose **Deploy from GitHub** and pick this repository.
2. Set the root directory to `app`. Detection finds PHP with Composer.
3. Deploy.

**Expected:** the app builds and serves the page in `app/index.php`.

**Actual:** the build fails at the Composer step:

```
#18 [build 10/11] RUN --mount=type=bind,source=composer.json,target=composer.json composer install --optimize-autoloader --ignore-platform-reqs --no-scripts --no-interaction
#18 ERROR: failed to calculate checksum of ref ...: "/composer.json": not found
 "/composer.json": not found. Please check if the files exist in the context.
Error: auto failed for /tmp/.tmpVJFtDB/out: Command depot build failed with exit code Some(1)
```

The dashboard shows the deploy as failed, with no reason.

To compare, zip the `app/` folder and deploy it with **Upload a ZIP**. It builds and serves the page.

## Why GitHub and ZIP behave differently

Both paths use the same detector (Anybuild 0.33.3 on production) and detect the same thing: PHP, Composer and the
same install and start commands. The difference is in how the backend prepares a GitHub build:

- **ZIP upload:** the backend extracts the archive and builds the folder itself. `composer.json` sits at the root
  of the build context, so the Composer step finds it.
- **GitHub with a root directory:** the backend clones the whole repository, uses the repository root as the build
  context, and passes the folder separately as `--subdir=app`. The Composer step mounts `composer.json` from the
  root of the build context, where it doesn't exist, so the build fails.
- **The start command is also fixed by the backend.** The backend saves the start command found at detection
  (`phpix -S 0.0.0.0:8080 -t /app`) and passes it back to the build as an override. With a root directory, `/app`
  holds the whole repository, not the folder, so the server points at the wrong directory.

So the failure comes from the backend's handling of the root directory, not from framework detection.

## Related: plain PHP in a subfolder

Without Composer, the same setup builds and reports success, but the site returns
**403 Directory listing forbidden**, because the server is pointed at the repository root (`-t /app`).

Deploying from GitHub with the start command set to `phpix -S 0.0.0.0:8080 -t /app/<folder>` makes it serve the
page with HTTP 200, which confirms the saved start command is the cause of the 403.

## Workarounds today

- Move the app to the repository root, or
- Deploy the folder as a ZIP.

## Suggested fixes

1. For GitHub deploys with a root directory, build from the folder itself, the way ZIP uploads do.
2. Don't pass a saved start command that assumes the app is at `/app` when a root directory is set.
3. Show the build error in the dashboard, and mark a deploy as failed when the site returns 4xx or 5xx.
