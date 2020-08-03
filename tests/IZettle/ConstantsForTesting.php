<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle;

final class ConstantsForTesting
{
    public const VALID_API_KEY = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6IjAifQ.eyJpc3MiOiJpWmV0dGxlIiwiYXVkIjoiQVBJIiwiZXhwIjoyNTM0Nzg1NzMxLCJzdWIiOiJhZmFiYzExOC00OWI4LTExZWEtODBlNi0wOWVlZWU0MGIxNjEiLCJpYXQiOjE1ODgwNzc5NTUsInJlbmV3ZWQiOmZhbHNlLCJzY29wZSI6WyJXUklURTpQUk9EVUNUIiwiUkVBRDpQUk9EVUNUIiwiUkVBRDpVU0VSSU5GTyIsIlJFQUQ6RklOQU5DRSIsIlJFQUQ6UFVSQ0hBU0UiXSwidXNlciI6eyJ1c2VyVHlwZSI6IlVTRVIiLCJ1dWlkIjoiYWZhYmMxMTgtNDliOC0xMWVhLTgwZTYtMDllZWVlNDBiMTYxIiwib3JnVXVpZCI6ImFmYWFlMmZjLTQ5YjgtMTFlYS05OGI5LWFkNzUwMjcxMTIxNCIsInVzZXJSb2xlIjoiT1dORVIifSwidHlwZSI6InVzZXItYXNzZXJ0aW9uIiwiY2xpZW50X2lkIjoiNTkxNjRjZTAtYjE2Ni0xMWVhLTgwOTEtNWU2NmNlNThiM2ZhIn0.VkXyBzrEOeUM1K9w5NhYqumcfShm738LMJG3JMW3FENrM90eGMZxfYaoY3jFYws2MkjktGShsf_8LQ4ZqDzeetREWQ8A0DPN2_0GXqbf-jZVGFwCR_Oxy2FBrBcIdjmeQMq_cX4siFd0aAxAcraA5IJIng81Jx1SEu4aA72apGylqW1l3oZ1YUXNgUd9zOj5OKPK_uhxMSLyJ8MD_fyXQH8BDUxJ8Y4dByJYDkXOzHz1C-uWEVrhIJ0OGVmEnh1Cxq2gtKyjQcz3rMZg2VN52GY_Yx2AcWlnjiwxf0nlMVSHegKyGfnVoyXIw-H4T2mA_R0NmixxT7teJ8NsPTd9NQ';
    public const INVALID_API_KEY = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6IjAifQ.eyJpc3MiOiJpWmV0dGxlIiwiYXVkIjoiQVBJIiwiZXhwIjoyNTM0Nzg1NzMxLCJzdWIiOiJhZmFiYzExOC00OWI4LTExZWEtODBlNi0wOWVlZWU0MGIxNjEiLCJpYXQiOjE1ODgwNzc5NTUsInJlbmV3ZWQiOmZhbHNlLCJzY29wZSI6W10sInVzZXIiOnsidXNlclR5cGUiOiJVU0VSIiwidXVpZCI6ImFmYWJjMTE4LTQ5YjgtMTFlYS04MGU2LTA5ZWVlZTQwYjE2MSIsIm9yZ1V1aWQiOiJhZmFhZTJmYy00OWI4LTExZWEtOThiOS1hZDc1MDI3MTEyMTQiLCJ1c2VyUm9sZSI6Ik9XTkVSIn0sInR5cGUiOiJ1c2VyLWFzc2VydGlvbiIsImNsaWVudF9pZCI6IjEyMzQ1Njc4OWEtYmNkZS1mMTIzLTQ1NjctODlhYmNkZWYxMjM0In0.VkXyBzrEOeUM1K9w5NhYqumcfShm738LMJG3JMW3FENrM90eGMZxfYaoY3jFYws2MkjktGShsf_8LQ4ZqDzeetREWQ8A0DPN2_0GXqbf-jZVGFwCR_Oxy2FBrBcIdjmeQMq_cX4siFd0aAxAcraA5IJIng81Jx1SEu4aA72apGylqW1l3oZ1YUXNgUd9zOj5OKPK_uhxMSLyJ8MD_fyXQH8BDUxJ8Y4dByJYDkXOzHz1C-uWEVrhIJ0OGVmEnh1Cxq2gtKyjQcz3rMZg2VN52GY_Yx2AcWlnjiwxf0nlMVSHegKyGfnVoyXIw-H4T2mA_R0NmixxT7teJ8NsPTd9NQ';

    public const LOCATION_STORE = '5475136c-69ca-11ea-b37d-6337db3424b3';
    public const LOCATION_BIN = '547513ee-69ca-11ea-911b-e8c34d434816';
    public const LOCATION_SUPPLIER = '54751312-69ca-11ea-8c71-0c48dee31184';
    public const LOCATION_SOLD = '54751394-69ca-11ea-9e37-9b5cf17b128a';

    public const DOMAIN = 'https://www.example.com/';

    public const PRODUCT_A_ID = 'fe1a0eabc10949fc8923ea07a37b33d6';
    public const PRODUCT_A_ID_CONVERTED = 'fe1a0eab-c109-19fc-8923-ea07a37b33d6';
    public const PRODUCT_A_ID_VARIANT = 'fe1a0eab-c109-19fc-8923-ea07a37b33d7';
    public const PRODUCT_B_ID = '7d2fb92e135f452cbecc3db235f9407c';
    public const PRODUCT_B_ID_CONVERTED = '7d2fb92e-135f-152c-becc-3db235f9407c';
    public const PRODUCT_B_ID_VARIANT = '7d2fb92e-135f-152c-becc-3db235f9407d';
    public const PRODUCT_C_ID = 'a07baa98123f4e64a8dec665c3d24ef5';
    public const PRODUCT_C_ID_CONVERTED = 'a07baa98-123f-1e64-a8de-c665c3d24ef5';
    public const PRODUCT_C_ID_VARIANT = 'a07baa98-123f-1e64-a8de-c665c3d24ef6';
    public const PRODUCT_D_ID = '4a8e2b914b084fee951252d950d5d227';
    public const PRODUCT_D_ID_CONVERTED = '4a8e2b91-4b08-1fee-9512-52d950d5d227';
    public const PRODUCT_D_ID_VARIANT = '4a8e2b91-4b08-1fee-9512-52d950d5d228';
    public const PRODUCT_E_ID = 'f3a9b5e2ed1a452db1a598a6ff32cecd';
    public const PRODUCT_E_ID_CONVERTED = 'f3a9b5e2-ed1a-152d-b1a5-98a6ff32cecd';
    public const PRODUCT_E_ID_VARIANT = 'f3a9b5e2-ed1a-152d-b1a5-98a6ff32cece';
    public const PRODUCT_F_ID = '308f915c928b4f878e5a7d4ba1dedc7b';
    public const PRODUCT_F_ID_CONVERTED = '308f915c-928b-1f87-8e5a-7d4ba1dedc7b';
    public const PRODUCT_F_ID_VARIANT = '308f915c-928b-1f87-8e5a-7d4ba1dedc7c';
    public const PRODUCT_G_ID_CONVERTED = '8450d162fd204e25bc470568420f881a';

    public const VARIANT_A_ID = 'c6482952242f4ac6bc1342313f77f16d';
    public const VARIANT_A_ID_CONVERTED = 'c6482952-242f-1ac6-bc13-42313f77f16d';
    public const VARIANT_B_ID = '613a6776247843329303088e8e095b47';
    public const VARIANT_B_ID_CONVERTED = '613a6776-2478-1332-9303-088e8e095b47';
    public const VARIANT_C_ID = '9330d162fd204e25bc470568420f882f';
    public const VARIANT_C_ID_CONVERTED = '9330d162-fd20-1e25-bc47-0568420f882f';
    public const VARIANT_D_ID = '785f1dc1ac6d4789938a960a973b7326';
    public const VARIANT_D_ID_CONVERTED = '785f1dc1-ac6d-1789-938a-960a973b7326';

    public const PRODUCT_DESCRIPTION = 'Product Description';
    public const PRODUCT_NUMBER = 'Number';
    public const PRODUCT_PRICE = 11.11;
    public const PRODUCT_PRICE_CONVERTED = 1111;
}
