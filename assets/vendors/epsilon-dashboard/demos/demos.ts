import './demos.scss';
import Vue from 'vue';
import { EpsilonFetchTranslator } from '../../epsilon-fetch-translator';

declare let EpsilonDashboard: any, wp: any, ajaxurl: string, jQuery: any;

/**
 * Multiple demo functionality
 * @type {ExtendedVue<VueConstructor, any, any, any, Record<never, any>>}
 */
export const dashboardDemos: any = Vue.extend( {
  /**
   * Demos component
   */
  name: 'demos',
  /**
   * Demo path
   */
  props: [ 'path' ],
  /**
   * Model
   * @returns {{}}
   */
  data: function() {
    return {
      availableDemos: [],
      currentDemo: null,
    };
  },
  methods: {
    /**
     * Import the selected demo
     * @param {string} id
     */
    importDemo: function( id: string ) {

    },
    /**
     * Toggle the advanced state of the demo importer
     * @param {number} index
     */
    selectDemo: function( index: number ) {
      if ( this.currentDemo === index ) {
        this.currentDemo = null;
        return;
      }

      this.currentDemo = index;
    }
  },
  /**
   * Template
   */
  template: `
    <transition-group tag="div" name="demo-complete" class="row" :class="{ epsilonDemoSelected: null !== currentDemo }">
      <div class="col epsilon-demo-box demo-complete-item" v-for="(demo, index) in availableDemos" :key="demo.id" v-if="null === currentDemo || index === currentDemo">
        <img :src="demo.thumb" />
            
          <ul class="epsilon-demo-box--advanced-list" v-show="index == currentDemo">
            <li v-for="content in demo.content" :key="content.id">
               <epsilon-toggle :comp-label="content.label" :comp-id="content.id"></epsilon-toggle>
            </li>
          </ul>

        <span class="epsilon-demo-title">{{ demo.label }}</span>
        <button class="button button-primary" @click="selectDemo(index)">{{ EpsilonDashboard.translations.import }}</button>
      </div>
    </div>
  `,
  /**
   * Before mount hook
   */
  beforeMount: function() {
    const self = this;
    let fetchObj: EpsilonFetchTranslator,
        data = {
          action: 'epsilon_dashboard_ajax_callback',
          nonce: EpsilonDashboard.ajax_nonce,
          args: {
            action: [ 'Epsilon_Dashboard_Helper', 'get_demos' ],
            nonce: EpsilonDashboard.ajax_nonce,
            args: {
              path: this.path
            },
          },
        };

    fetchObj = new EpsilonFetchTranslator( data );

    fetch( ajaxurl, fetchObj ).then( function( res ) {
      return res.json();
    } ).then( function( json ) {
      if ( 'ok' === json.status ) {
        for ( let key in json.demos ) {
          self.availableDemos.push( json.demos[ key ] );
        }
      }
    } );

  }
} );
Vue.component( 'demos', dashboardDemos );