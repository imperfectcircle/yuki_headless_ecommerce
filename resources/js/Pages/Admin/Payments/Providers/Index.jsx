import { router } from "@inertiajs/react";

export default function PaymentProviders({ providers }) {
    return (
        <div>
            <h1>Payment Providers</h1>

            <ul>
                {providers.map((provider) => (
                    <li key={provider.id}>
                        <strong>{provider.code}</strong>

                        <button
                            onClick={() =>
                                router.patch(
                                    route(
                                        "admin.payment-providers.toggle",
                                        provider.id
                                    )
                                )
                            }
                        >
                            {provider.enabled ? "Disable" : "Enable"}
                        </button>
                    </li>
                ))}
            </ul>
        </div>
    );
}
