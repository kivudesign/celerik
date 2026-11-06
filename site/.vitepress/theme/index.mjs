import DefaultTheme from 'vitepress/theme';
import Demo from './components/Demo.vue';
import './custom.css';

// The default theme, plus <Demo name="…" /> for live component demos (site/demos/).
export default {
  extends: DefaultTheme,
  enhanceApp({ app }) {
    app.component('Demo', Demo);
  },
};
