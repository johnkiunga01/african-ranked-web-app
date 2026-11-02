import { defineRailway, github, project, service, volume } from "railway/iac";
// This repository manages only its own resources in the environment. Other
// repositories export their own partial name.
// See https://docs.railway.com/infrastructure-as-code#multi-repo-projects
export const partial = "african-ranked-web-app";
export default defineRailway(() => {
  const storage = volume("african-ranked-web-app-volume", { region: "sfo", sizeMB: 50000 });
  const africanRankedWebApp = service("african-ranked-web-app", {
    source: github("johnkiunga01/african-ranked-web-app", { branch: "laravel/staging-platform", checkSuites: false }),
    build: { buildEnvironment: "V3", builder: "DOCKERFILE", dockerfilePath: "Dockerfile" },
    volumeMounts: { "/app/storage": storage },
    healthcheck: "/up",
    healthcheckTimeout: 120,
    preDeploy: "sh -lc 'php artisan migrate --force && php artisan content:sync && php artisan content:optimize-onsite'",
    replicas: { "sfo": 1 },
  });

  const worker = service("african-ranked-worker", {
    source: github("johnkiunga01/african-ranked-web-app", { branch: "laravel/staging-platform", checkSuites: false }),
    build: { buildEnvironment: "V3", builder: "DOCKERFILE", dockerfilePath: "Dockerfile" },
    replicas: { "sfo": 1 },
  });
  const scheduler = service("african-ranked-scheduler", {
    source: github("johnkiunga01/african-ranked-web-app", { branch: "laravel/staging-platform", checkSuites: false }),
    build: { buildEnvironment: "V3", builder: "DOCKERFILE", dockerfilePath: "Dockerfile" },
    replicas: { "sfo": 1 },
  });
  return project("Africa Ranked", {
    variables: { managed: false },
    resources: [storage, africanRankedWebApp, worker, scheduler],
  });
});

