declare let require: any, wp: any;
import Vue from 'vue';
import Vuex from 'vuex';

import './dashboard.scss';
import { dashboardContainer } from './dashboard-container/dashboard-container';
import { dashboardPlugins } from '../epsilon-common/plugins/plugins';
import { dashboardRecommendedActions } from '../epsilon-common/recommended-actions/recommended-actions';
import { dashboardDemos } from '../epsilon-common/demos/demos';
import { dashboardRegistration } from '../epsilon-common/registration/registration';
import { epsilonToggle } from '../epsilon-common/epsilon-fields/epsilon-toggle/epsilon-toggle';

import Store from '../epsilon-common/store/store';

const epsilonDashboardVue = new Vue( {
  /**
   * Element
   */
  el: '#epsilon-dashboard-app',
  /**
   * Store
   */
  store: Store,
  /**
   * App components
   */
  components: {
    'dashboard-container': dashboardContainer,
    'recommended-actions': dashboardRecommendedActions,
    'demos': dashboardDemos,
    'plugins': dashboardPlugins,
    'registration': dashboardRegistration,
    'epsilon-toggle': epsilonToggle,
  },
  /**
   * Template
   */
  template: `<dashboard-container></dashboard-container>`,
} );
