#!/usr/bin/env python3
"""Clean expected YAML files by removing dynamic fields that change between runs."""

import sys
import yaml


def clean_step(step: dict) -> dict | None:
    """Clean a single step, keeping only stable fields."""
    cleaned = {}

    if "data" in step and step["data"]:
        data = {}
        if "action" in step["data"] and step["data"]["action"]:
            data["action"] = step["data"]["action"]
        if "expected_result" in step["data"] and step["data"]["expected_result"]:
            data["expected_result"] = step["data"]["expected_result"]
        if data:
            cleaned["data"] = data

    if "execution" in step and step["execution"]:
        execution = {}
        if "status" in step["execution"]:
            execution["status"] = step["execution"]["status"]
        if execution:
            cleaned["execution"] = execution

    if "steps" in step and step["steps"]:
        child_steps = [clean_step(s) for s in step["steps"]]
        child_steps = [s for s in child_steps if s]
        if child_steps:
            cleaned["steps"] = child_steps

    if "attachments" in step and step["attachments"]:
        attachments = [clean_attachment(a) for a in step["attachments"]]
        attachments = [a for a in attachments if a]
        if attachments:
            cleaned["attachments"] = attachments

    return cleaned if cleaned else None


def clean_attachment(attachment: dict) -> dict | None:
    """Clean attachment, keeping only file_name and mime_type."""
    cleaned = {}
    if "file_name" in attachment and attachment["file_name"]:
        cleaned["file_name"] = attachment["file_name"]
    if "mime_type" in attachment and attachment["mime_type"]:
        cleaned["mime_type"] = attachment["mime_type"]
    return cleaned if cleaned else None


def clean_result(result: dict) -> dict:
    """Clean a single test result."""
    cleaned = {}

    # Keep stable identification fields
    if "title" in result:
        cleaned["title"] = result["title"]
    if "signature" in result:
        cleaned["signature"] = result["signature"]
    if "testops_ids" in result and result["testops_ids"]:
        cleaned["testops_ids"] = result["testops_ids"]

    # Keep top-level status
    if "status" in result:
        cleaned["status"] = result["status"]
    elif "execution" in result and "status" in result["execution"]:
        cleaned["status"] = result["execution"]["status"]

    # Keep non-empty fields
    if "fields" in result and result["fields"]:
        cleaned["fields"] = result["fields"]

    # Keep non-empty params
    if "params" in result and result["params"]:
        cleaned["params"] = result["params"]

    # Keep non-empty param_groups
    if "param_groups" in result and result["param_groups"]:
        cleaned["param_groups"] = result["param_groups"]

    # Keep relations
    if "relations" in result and result["relations"]:
        cleaned["relations"] = result["relations"]

    # Clean and keep non-empty steps
    if "steps" in result and result["steps"]:
        steps = [clean_step(s) for s in result["steps"]]
        steps = [s for s in steps if s]
        if steps:
            cleaned["steps"] = steps

    # Clean and keep non-empty attachments
    if "attachments" in result and result["attachments"]:
        attachments = [clean_attachment(a) for a in result["attachments"]]
        attachments = [a for a in attachments if a]
        if attachments:
            cleaned["attachments"] = attachments

    # Remove dynamic fields:
    # - execution block (except top-level status already extracted)
    # - message (contains timestamps and unstable whitespace)
    # - stacktrace (contains absolute file paths)
    # - muted: false (default value)

    return cleaned


def clean_expected(data: dict) -> dict:
    """Clean the entire expected data structure."""
    cleaned = {}

    # Preserve run stats from the report (produced by the reporter)
    if "run" in data and "stats" in data["run"]:
        cleaned["run"] = {"stats": data["run"]["stats"]}

    if "results" in data:
        results = [clean_result(r) for r in data["results"]]
        cleaned["results"] = results

    return {"run": cleaned.get("run", {}), "results": cleaned.get("results", [])}


def main():
    if len(sys.argv) < 2:
        print(f"Usage: {sys.argv[0]} <file1.yaml> [file2.yaml ...]", file=sys.stderr)
        sys.exit(1)

    for path in sys.argv[1:]:
        with open(path, "r") as f:
            data = yaml.safe_load(f)

        cleaned = clean_expected(data)

        with open(path, "w") as f:
            yaml.dump(cleaned, f, default_flow_style=False, allow_unicode=True, sort_keys=False)

        print(f"Cleaned: {path}")


if __name__ == "__main__":
    main()
