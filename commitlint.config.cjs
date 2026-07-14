module.exports = {
  extends: ["@commitlint/config-conventional"],
  ignores: [
    (message) =>
      /^(?:chore(?:\([^)]+\))?:\s*)?bump\b/i.test(message) ||
      /^Bumps?\b/i.test(message),
  ],
};
