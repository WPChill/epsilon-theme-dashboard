declare let require: any, wp: any;
import * as $ from 'jquery';
import Vue from 'vue';
import './onboarding.scss';
import { onboardingContainer } from './onboarding-container/onboarding-container';

const epsilonOnboardingVue = new Vue( {
  /**
   * Element
   */
  el: '#epsilon-onboarding-app',
  /**
   * App components
   */
  components: {
    'onboarding-container': onboardingContainer,
  },
  /**
   * Template
   */
  template: `<onboarding-container></onboarding-container>`,
} );
