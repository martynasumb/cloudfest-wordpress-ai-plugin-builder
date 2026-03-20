# WordPress AI Plugin Builder

A WordPress admin plugin that lets you describe a plugin in plain text, then uses an LLM to generate, preview, and install it — all from a chat UI inside your WordPress dashboard.

## What it does

1. You type what you want — e.g. _"Create a plugin that adds a cookie consent banner"_
2. The AI generates a structured plan (files + descriptions)
3. You review the plan, then the AI generates the actual PHP files
4. A security scan runs automatically
5. You click **Install** — the plugin is written to disk and activated

Supports both **OpenAI** and **Anthropic** as AI providers, configurable from the Settings page.

---

## Requirements

- WordPress 6.0+
- PHP 8.2+
- Node.js 18+ (for frontend build)
- Composer
- An OpenAI or Anthropic API key

---

## Installation

### 1. Clone the repo

```bash
git clone git@github.com:martynasumb/cloudfest-wordpress-ai-plugin-builder.git
cd cloudfest-wordpress-ai-plugin-builder
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Build the frontend

```bash
cd frontend
npm install
npm run build
cd ..
```

This compiles the Vue 3 + TypeScript frontend and outputs it to `assets/dist/`.

### 4. Place in WordPress

Copy (or symlink) the plugin folder to your WordPress plugins directory:

```bash
cp -r . /path/to/wp-content/plugins/wordpress-ai-plugin-builder
```

### 5. Activate the plugin

Go to **WordPress Admin → Plugins** and activate **WordPress AI Plugin Builder**.

### 6. Configure API key

Go to **WordPress Admin → AI Plugin Builder → Settings** and enter your OpenAI or Anthropic API key.

---

## Development

### Frontend (Vue 3 + TypeScript)

```bash
cd frontend
npm install
npm run dev     # watch mode
npm run build   # production build → assets/dist/
```

### Backend (PHP)

No build step needed. PHP files are autoloaded via Composer PSR-4.

```bash
composer install   # install autoloader
composer dump-autoload   # regenerate autoloader after adding classes
```

---

## Architecture

```
wordpress-ai-plugin-builder.php   # Plugin entry point
src/
  Plugin.php                      # Singleton bootstrap
  Config.php                      # Reads WP options (API key, model, provider)
  Ai/
    AiClient.php                  # HTTP client for OpenAI + Anthropic
    Pipeline.php                  # Plan → Code → Security scan
    BackgroundJob.php             # Non-blocking background processing
    IntentDetector.php            # Classifies user intent via LLM
    Prompts.php                   # All LLM prompt templates
    SecurityScanner.php           # Regex-based security scan
  Rest/
    GenerateController.php        # POST /generate
    StatusController.php          # GET  /status/:job_id
    InstallController.php         # POST /install
  Installer/
    PluginWriter.php              # Writes files via WP_Filesystem
    PluginActivator.php           # Activates generated plugin
  Admin/
    Menu.php                      # Admin menu
    Assets.php                    # Enqueue Vite assets
    SettingsPage.php              # Settings UI
frontend/src/
  components/ChatWindow.vue       # Main chat UI
  composables/usePluginBuilder.ts # Core state and actions
  repositories/pluginBuilderRepository.ts  # API calls
```

## REST API

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/wordpress-ai-plugin-builder/v1/generate` | Start generation, returns `job_id` |
| GET  | `/wordpress-ai-plugin-builder/v1/status/:job_id` | Poll job status |
| POST | `/wordpress-ai-plugin-builder/v1/install` | Write and activate the plugin |

---

## License

GPL-2.0-or-later
