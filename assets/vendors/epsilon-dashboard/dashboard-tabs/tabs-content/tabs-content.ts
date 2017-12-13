import './tabs-content.scss';
import Vue from 'vue';

declare let EpsilonDashboard: any, wp: any;

/**
 * Tabs component
 * @type {ExtendedVue<VueConstructor, any, any, any, Record<never, any>>}
 */
export const dashboardTabsContent: any = Vue.extend( {
  /**
   * Component name
   */
  name: 'dashboard-tabs-content',
  /**
   * Model
   * @returns {{currentTab: null}}
   */
  data: function() {
    return {
      EpsilonDashboard: EpsilonDashboard,
      currentTab: EpsilonDashboard.tabs[ 0 ].id,
    };
  },
  /**
   * Component template
   */
  template: `
      <div>
        <template v-for="(tab, index) in EpsilonDashboard.tabs">
          <div class="epsilon-dashboard-tab" :class="{ active: currentTab === tab.id }" :id="tab.id" :key="tab.id">
          
            <template v-if="tab.type === 'info'">
              <div class="row">
                  <div class="col" v-for="col in tab.content" :class="{ standout: col.type === 'standout' }">
                      <h3>{{ col.title }}</h3>
                      <p v-html="col.paragraph"></p>
                      <p v-html="col.action"></p>
                  </div>
              </div>        
            </template>
            
            <template v-else-if="tab.type === 'actions'">
                <recommended-actions></recommended-actions>
            </template>
            
            <template v-else-if="tab.type === 'demos'">
              <h3>{{ tab.content.title }}</h3>
              <p v-html="tab.content.paragraph"></p>
              <demos :path="tab.content.demos"></demos>
            </template>
          </div>
        </template>
      </div>
  `,
  /**
   * Methods
   */
  methods: {
    changeTab: function( id: string ): void {
      this.currentTab = id;
    }
  },
  /**
   * Created hook
   */
  created: function(): void {
    this.$root.$on( 'change-tab', this.changeTab );
  }
} );
Vue.component( 'dashboard-tabs-content', dashboardTabsContent );
