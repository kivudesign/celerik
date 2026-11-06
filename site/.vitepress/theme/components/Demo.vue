<script setup>
// A live demo: site/public/demos/<name>.html (rendered by site/demos/build.php) in an
// iframe, so Bootstrap's styles never reach the documentation pages. It follows the
// site's dark mode, can switch the base look, and grows with its content.
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useData, withBase } from 'vitepress';

const props = defineProps({
  name: { type: String, required: true },
  title: { type: String, default: '' },
  minHeight: { type: Number, default: 160 },
});

const { isDark } = useData();
const theme = ref('bootstrap');
const frame = ref(null);
const height = ref(props.minHeight);
const label = computed(() => props.title || props.name.replace(/-/g, ' ').replace(/^./, (c) => c.toUpperCase()));
const src = computed(() => withBase(`/demos/${props.name}.html?theme=${theme.value}&mode=${isDark.value ? 'dark' : 'light'}`));
const selectId = `demo-theme-${props.name}`;

let observer = null;

function measure() {
  const doc = frame.value?.contentDocument;
  if (doc?.documentElement) {
    height.value = Math.max(props.minHeight, Math.ceil(doc.documentElement.scrollHeight));
  }
}

function onLoad() {
  observer?.disconnect();
  const doc = frame.value?.contentDocument;
  if (!doc) {
    return;
  }
  observer = new ResizeObserver(measure);
  observer.observe(doc.documentElement);
  measure();
}

watch(src, () => {
  height.value = Math.max(props.minHeight, height.value);
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
  <figure class="celerik-demo">
    <div class="celerik-demo__bar">
      <label :for="selectId">Look</label>
      <select :id="selectId" v-model="theme">
        <option value="bootstrap">Bootstrap</option>
        <option value="material">Material</option>
        <option value="tailwind">Tailwind</option>
      </select>
      <a :href="src" target="_blank" rel="noopener">Open the demo in a new tab<span class="visually-hidden"> ({{ label }})</span></a>
    </div>
    <iframe
      ref="frame"
      :src="src"
      :title="`Demo: ${label}`"
      :style="{ height: `${height}px` }"
      loading="lazy"
      @load="onLoad"
    />
    <figcaption>{{ label }} demo. Data is embedded and nothing is saved.</figcaption>
  </figure>
</template>
