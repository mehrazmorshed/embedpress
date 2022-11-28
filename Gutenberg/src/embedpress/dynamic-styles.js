import react from 'react';
import { isOpensea, DynamicStyleOpensea } from "./InspectorControl/opensea";

export const dynamicStyles = ({
    url,
	clientId,
	width,
	height,
	...attributes
}) => {
	return (
		<React.Fragment>
			{/* {isYTChannel && (
				<style style={{ display: "none" }}>
					{`
                    #block-${clientId} .ep-youtube__content__block .youtube__content__body .content__wrap{
                        gap: ${gapbetweenvideos}px!important;
                        margin-top: ${gapbetweenvideos}px!important;
                    }

                    #block-${clientId} .ose-youtube{
                        width: ${width}px!important;
                    }
                    #block-${clientId} .ose-youtube .ep-first-video iframe{
                        max-height: ${height}px!important;
                    }

                    #block-${clientId} .ose-youtube > iframe{
                        height: ${height}px!important;
                        width: ${width}px!important;
                    }

                    #block-${clientId} .ep-youtube__content__block .youtube__content__body .content__wrap {
                        grid-template-columns: ${repeatCol};
                    }

                    #block-${clientId} .ep-youtube__content__block .ep-youtube__content__pagination{
                        display: flex!important;
                    }

                    ${
											!ispagination &&
											`#block-${clientId} .ep-youtube__content__block .ep-youtube__content__pagination{
                            display: none!important;
                        }`
										}

                    `}
				</style>
			)} */}

            {/* !isYTChannel && */}
			{!isOpensea(url) && (
				<style style={{ display: "none" }}>
					{`
                    #block-${clientId} .ose-embedpress-responsive{
                        width: ${width}px!important;
                        height: ${height}px!important
                    }
                    #block-${clientId} iframe{
                        width: ${width}px!important;
                        height: ${height}px!important
                    }
                    #block-${clientId} .ose-youtube > iframe{
                        height: ${height}px!important;
                        width: ${width}px!important;
                    }
                    #block-${clientId} .ose-youtube{
                        height: ${height}px!important;
                        width: ${width}px!important;
                    }
                `}
				</style>
			)}
            {/* we can also use filters instead. */}
			<DynamicStyleOpensea {...attributes} />
		</React.Fragment>
	);
};

export default dynamicStyles;