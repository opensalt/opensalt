const bootstrapConfig = window.openSaltEditor || {};
const featureConfig = bootstrapConfig.features || {};

export function isEditorFeatureEnabled(featureName) {
  return featureConfig[featureName] === true;
}

export const editorConfig = {
  features: {
    comments: isEditorFeatureEnabled('comments'),
    useLocalFrameworkDb: isEditorFeatureEnabled('useLocalFrameworkDb'),
    useLocalAssociationQueries: isEditorFeatureEnabled('useLocalAssociationQueries'),
    useLocalTreeQueries: isEditorFeatureEnabled('useLocalTreeQueries'),
    useLocalSimilaritySearch: isEditorFeatureEnabled('useLocalSimilaritySearch'),
  },
};
