/**
 *
 * State getters
 *
 */
export const getters = {
  /**
   * Get imported state
   * @param state
   * @returns {() => any}
   */
  getImportStatus: function( state: any ) {
    return state.importedDemo;
  },
  /**
   * Gets the field value based on a "relation"
   * @param state
   */
  getFieldRelation: ( state: any ) => ( id: string ) => {
    return state.privacy[ id ];
  }
};
