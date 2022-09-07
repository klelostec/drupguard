import createConfig from "react-runtime-config";

export const { useConfig } = createConfig({
    namespace: "APP_CONFIG",
    schema: {
        backend: {
            type: "string",
            description: "Backend url",
        },
        token: {
            type: "string",
            description: "Token",
        },
        interval: {
            type: "number",
            description: "Interval",
            min: 0,
            default: 5000,
        },
        refresh: {
            type: "number",
            description: "Refresh time",
            min: 0,
            default: 60*60*1000,
        },
    }
});