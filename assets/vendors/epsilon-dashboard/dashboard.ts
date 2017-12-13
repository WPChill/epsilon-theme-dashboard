declare let require: any, wp: any;
import Vue from 'vue';
import Vuex from 'vuex';

import './dashboard.scss';
import { dashboardContainer } from './dashboard-container/dashboard-container';
import { dashboardRecommendedActions } from './recommended-actions/recommended-actions';
import { dashboardDemos } from './demos/demos';
import { epsilonToggle } from '../epsilon-fields/epsilon-toggle/epsilon-toggle';

import Store from './store/store';

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
    'epsilon-toggle': epsilonToggle,
  },
  /**
   * Template
   */
  template: `<dashboard-container></dashboard-container>`,
} );
