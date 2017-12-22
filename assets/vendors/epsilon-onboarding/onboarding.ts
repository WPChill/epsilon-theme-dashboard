import { dashboardPluginsQueue } from '../epsilon-common/plugins-queue/plugins-queue';

declare let require: any, wp: any;
import Vue from 'vue';
import Store from './store/store';

import './onboarding.scss';
import { dashboardDemos } from '../epsilon-common/demos/demos';
import { onboardingContainer } from './onboarding-container/onboarding-container';
import { epsilonToggle } from '../epsilon-common/epsilon-fields/epsilon-toggle/epsilon-toggle';
import { dashboardPlugins } from '../epsilon-common/plugins/plugins';
import { dashboardOptionPage } from '../epsilon-common/option-page/option-page';

const epsilonOnboardingVue = new Vue( {
  /**
   * Element
   */
  el: '#epsilon-onboarding-app',
  /**
   * Store
   */
  store: Store,
  /**
   * App components
   */
  components: {
    'onboarding-container': onboardingContainer,
    'plugins': dashboardPlugins,
    'plugins-queue': dashboardPluginsQueue,
    'demos': dashboardDemos,
    'option-page': dashboardOptionPage,
    'epsilon-toggle': epsilonToggle,
  },
  /**
   * Template
   */
  template: `<onboarding-container></onboarding-container>`,
} );
