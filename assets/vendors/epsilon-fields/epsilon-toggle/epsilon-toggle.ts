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
  props: [ 'compId', 'compLabel', 'parentIndex' ],
  /**
   * Model
   * @returns {{active: boolean}}
   */
  data: function() {
    return {
      active: true,
    };
  },
  methods: {
    sendCheckboxChange: function() {
      this.$nextTick( function() {
        this.$root.$emit( 'changed-epsilon-toggle', { id: this.compId, status: this.active, parentIndex: this.parentIndex } );
      } );
    }
  },
  /**
   * Component template
   */
  template: `
		<div class="checkbox_switch">
      <div class="onoffswitch">
        <input type="checkbox" v-on:input="sendCheckboxChange" :id="'epsilon-' + compId" :name="'epsilon-' + compId" v-model="active" class="onoffswitch-checkbox" :checked="active">
        <label class="onoffswitch-label" :for="'epsilon-' + compId"></label>
      </div>
      {{ compLabel }}
		</div>
  `,

} );
Vue.component( 'epsilon-toggle', epsilonToggle );