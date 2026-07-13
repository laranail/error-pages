<script setup lang="ts">
/**
 * The Vue error-page component — a faithful port of the shared DOM contract
 * (.ep-shell / .ep-card / .ep-brand / .ep-status / .ep-title / .ep-message /
 * .ep-actions), driven by the one ErrorPagePayload. Styled by the shared CSS
 * (`@laranail/error-pages-ui/style.css`); the final design lands with the
 * canonical templates. Used by the Inertia and Vue-SPA stacks.
 */
import type { ErrorPagePayload } from '../../shared/ts/payload';
import { themeClass } from '../../shared/ts/payload';

const props = defineProps<{ page: ErrorPagePayload }>();
</script>

<template>
  <div :class="themeClass(props.page)">
    <main class="ep-shell" role="main">
      <section class="ep-card">
        <div class="ep-brand">
          <img
            v-if="props.page.brand.logo"
            class="ep-logo"
            :src="props.page.brand.logo"
            :alt="props.page.brand.name"
          />
          <span v-else class="ep-brand-name">{{ props.page.brand.name }}</span>
        </div>
        <p class="ep-status" aria-hidden="true">{{ props.page.code }}</p>
        <h1 class="ep-title">{{ props.page.title }}</h1>
        <p class="ep-message">{{ props.page.message }}</p>
        <div class="ep-actions">
          <a class="ep-btn ep-btn-primary" :href="props.page.homeUrl">Back to home</a>
        </div>
        <p v-if="props.page.requestId" class="ep-ref">
          Reference: <code>{{ props.page.requestId }}</code>
        </p>
      </section>
    </main>
  </div>
</template>
