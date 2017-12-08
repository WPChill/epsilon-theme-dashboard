import './epsilon-toggle.scss';
import Vue from 'vue';

declare let EpsilonDashboard: any, wp: any;

/**
 * Epsilon Toggle field
 * @type {ExtendedVue<VueConstructor, any, any, any, Record<never, any>>}
 */
export const epsilonToggle: any = Vue.extend( {
  /**
   * Component name
   */
  name: 'epsilon-toggle',
  /**
   * Accepted props
   */
  props: [ 'compId', 'compLabel' ],
  /**
   * Component template
   */
  template: `
		<div class="checkbox_switch">
		  {{ compLabel }}
      <div class="onoffswitch">
        <input type="checkbox" :id="'epsilon-' + compId" :name="'epsilon-' + compId" class="onoffswitch-checkbox" value="on">
        <label class="onoffswitch-label" :for="'epsilon-' + compId"></label>
      </div>
		</div>
  `,
} );
Vue.component( 'epsilon-toggle', epsilonToggle );