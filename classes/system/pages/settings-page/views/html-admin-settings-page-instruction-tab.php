<?php
/**
 * The Instruction tab
 * 
 * @version 0.3.1 (25-05-2024)
 * @see     
 * @package 
 * 
 * @param 
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="postbox">
	<h2 class="hndle">
		<?php esc_html_e( 'Instruction', 'import-products-to-ozon' ); ?>
	</h2>
	<div class="inside">
		<p><i>(
				<?php esc_html_e( 'The full version of the instruction can be found', 'import-products-to-ozon' );
				?> <a href="<?php
				 printf( '%1$s?utm_source=%2$s&utm_medium=organic&utm_campaign=in-plugin-%2$s%3$s',
				 	'https://icopydoc.ru/nastrojka-plagina-import-products-to-ozon/',
				 	'import-products-to-ozon',
				 	'&utm_content=api-set-page&utm_term=main-instruction'
				 ); ?>" target="_blank">
					<?php esc_html_e( 'here', 'import-products-to-ozon' ); ?>
				</a>)
			</i></p>
		<p>
			<?php esc_html_e( 'To access OZON API you need', 'import-products-to-ozon' ); ?>:
		</p>
		<ol>
			<?php printf( '<li><a href="%1$s target="_blank">%2$s</a></li>',
				'https://seller.ozon.ru/app/settings/api-keys',
				esc_html__( 'Generate an API key on the OZON', 'import-products-to-ozon' )
			); ?>
		</ol>
		<p><img style="max-width: 100%;" src="<?php echo IP2OZ_PLUGIN_DIR_URL; ?>screenshot-3.png"
				alt="screenshot-3.png" /></p>
		<ol>
			<?php
			printf( '<li value="2">%1$s "Client-Id" %2$s "Api-Key" %3$s "%4$s"</li>',
				esc_html__( 'Fill in the fields', 'import-products-to-ozon' ),
				esc_html__( 'and', 'import-products-to-ozon' ),
				esc_html__( 'on the tab', 'import-products-to-ozon' ),
				esc_html__( 'API Settings', 'import-products-to-ozon' )
			);
			?>
			<?php
			printf( '<li value="3">%1$s "%2$s" %3$s. %4$s. %5$s "%6$s. %7$s"</li><li>%8$s</li>',
				esc_html__(
					'After filling in the fields and saving the settings, the',
					'import-products-to-ozon'
				),
				esc_html__( 'Check API', 'import-products-to-ozon' ),
				esc_html__( 'button will appear', 'import-products-to-ozon' ),
				esc_html__( 'Click on it to test the API', 'import-products-to-ozon' ),
				esc_html__(
					'If everything is configured correctly, you will see the message',
					'import-products-to-ozon'
				),
				esc_html__( 'API connection was successful', 'import-products-to-ozon' ),
				esc_html__( 'Now you can go to step 4 of the instructions', 'import-products-to-ozon' ),
				esc_html__(
					'After configuring the API, edit your categories on the site by selecting a similar category for each of them in OZON',
					'import-products-to-ozon'
				)
			);
			?>
		</ol>
		<p><img style="max-width: 100%;" src="<?php echo IP2OZ_PLUGIN_DIR_URL; ?>screenshot-2.png"
				alt="screenshot-2.png" /></p>
		<ol>
			<li value="5">
				<?php
				esc_html_e( 'Be sure to specify the weight and dimensions of all products',
					'import-products-to-ozon'
				); ?>
			</li>
		</ol>
		<p><img style="max-width: 100%;" src="<?php echo IP2OZ_PLUGIN_DIR_URL; ?>assets/img/instruction-1.png"
				alt="screenshot-2.png" /></p>
		<ol>
			<li value="6">
				<?php
				printf( '%s "%s" <strong style="color: red;">%s</strong>. %s',
					esc_html__( 'In the field', 'import-products-to-ozon' ),
					esc_html__( 'Include these attributes in the import', 'import-products-to-ozon' ),
					esc_html__( 'specify attributes containing information about the color and brand of the products on your site',
						'import-products-to-ozon'
					),
					esc_html__( 'You can also mark any other attributes that you want to import',
						'import-products-to-ozon'
					)
				);
				?>
			</li>
		</ol>
		<p><img style="max-width: 100%;" src="<?php echo IP2OZ_PLUGIN_DIR_URL; ?>assets/img/instruction-4.png"
				alt="screenshot-4.png" /></p>
		<ol>
			<li value="7">
				<?php
				printf( '%s. %s',
					esc_html_e(
						'Edit all attribute values by matching them with the values from the OZON directory',
						'import-products-to-ozon'
					),
					esc_html__(
						'To do this, go to "Products" - "Attributes" and click on the "Configure terms" link under the list of values',
						'import-products-to-ozon'
					)
				); ?>
			</li>
		</ol>
		<p><img style="max-width: 100%;" src="<?php echo IP2OZ_PLUGIN_DIR_URL; ?>assets/img/instruction-5.png"
				alt="screenshot-5.png" /></p>
		<ol>
			<li value="8">
				<?php
				printf( '%s "%s". %s "%s"',
					esc_html__(
						'Select the appropriate value in the field',
						'import-products-to-ozon'
					),
					esc_html__( 'Attribute value on OZON', 'import-products-to-ozon' ),
					esc_html__(
						'To display all three fields, after selecting a value in each of them, click',
						'import-products-to-ozon'
					),
					esc_html__( 'Update', 'import-products-to-ozon' )
				);
				?>
			</li>
		</ol>
		<p><img style="max-width: 100%;" src="<?php echo IP2OZ_PLUGIN_DIR_URL; ?>assets/img/instruction-6.png"
				alt="screenshot-6.png" /></p>
		<ol>
			<li value="9">
				<?php
				printf( '%s "%s" %s "%s OZON" %s',
					esc_html__( 'After that, go to the', 'import-products-to-ozon' ),
					esc_html__( 'Main settings', 'import-products-to-ozon' ),
					esc_html__( 'and activate the item', 'import-products-to-ozon' ),
					esc_html__( 'Syncing with', 'import-products-to-ozon' ),
					esc_html__(
						'After that, when editing any of the products on your site, the data will be imported into OZON',
						'import-products-to-ozon'
					)
				);
				?>
			</li>
		</ol>
	</div>
</div>