# BOB Reconciliation Hub
## Flow Diagrams

Version: 2026-04-08

This page was redesigned for readability:
- Each diagram follows one clear story.
- Colors and grouping are consistent.
- Technical details are kept, but visual noise is reduced.

## 1) End-to-End System Architecture
Use this as the master map of how UI, services, jobs, and storage connect.

```mermaid
%%{init: {"theme":"base","themeVariables":{"primaryColor":"#1f2937","primaryTextColor":"#e5e7eb","lineColor":"#64748b","tertiaryColor":"#0f172a","fontFamily":"Segoe UI"},"flowchart":{"curve":"basis","nodeSpacing":38,"rankSpacing":48}} }%%
flowchart LR
    subgraph UX[User Experience]
        U[Analyst or Manager]
        UI[Blade UI + Alpine]
    end

    subgraph APP[Application Layer]
        CTRL[Reconciliation Controllers]
        ORCH[ReconciliationService / ContractPatchService]
        FILES[FileUploadService]
        RECOPS[RecordController Actions]
        REP[Reporting Controllers]
    end

    subgraph ASYNC[Async Processing]
        JOBS[Queue Jobs]
        ETL[ReconciliationETLService]
        MAPS[IMS / HS / Payee / LockList Maps]
    end

    subgraph DATA[Data + Artifacts]
        IB[(import_batches)]
        RQ[(reconciliation_queue)]
        IRE[(import_row_errors)]
        CPL[(contract_patch_logs)]
        AUD[(reconciliation_audit_logs)]
        LL[(lock_lists)]
        OUT[(output files)]
    end

    subgraph OPS[Operations]
        SCH[Scheduler]
        REL[ReleaseExpiredLocksJob]
        ARC[ArchiveResolvedRecordsJob]
    end

    U --> UI --> CTRL --> ORCH
    ORCH --> FILES
    ORCH --> IB
    ORCH --> JOBS --> ETL --> MAPS
    ETL --> RQ
    ETL --> IRE
    ETL --> CPL
    ETL --> OUT

    UI --> RECOPS
    RECOPS --> RQ
    RECOPS --> AUD
    RECOPS --> LL

    UI --> REP
    REP --> RQ
    REP --> CPL
    REP --> OUT

    SCH --> REL --> RQ
    SCH --> ARC --> RQ
```

## 2) Standard Synchronization Flow
This shows the lifecycle of a normal upload run from submit to output generation.

```mermaid
sequenceDiagram
    autonumber
    actor Analyst
    participant Upload as UploadController
    participant Service as ReconciliationService
    participant Batch as import_batches
    participant Job as ProcessImportBatchJob
    participant ETL as ReconciliationETLService
    participant Queue as reconciliation_queue
    participant Errors as import_row_errors

    Analyst->>Upload: Submit Carrier + IMS/HS (+ optional Payee)
    Upload->>Service: startSynchronization(dto)
    Service->>Batch: Create batch (status = pending)
    Service->>Job: Dispatch async processing
    Upload-->>Analyst: 202 Accepted

    Job->>ETL: processBatch(batch)
    ETL->>Batch: Mark status = processing
    ETL->>ETL: Build source lookup maps

    loop For each carrier row
        ETL->>ETL: Run IMS matching stage
        ETL->>ETL: Run Health Sherpa stage
        ETL->>ETL: Apply Lock List final override
        ETL->>Queue: Insert normalized row + match metadata
    end

    ETL->>Errors: Persist row-level failures
    ETL->>Batch: Update counters + final status
    ETL->>Batch: Save output_file_path
```

## 3) Deterministic Matching Decision Path
Decision order is strict: IMS first, then Health Sherpa, then Lock List override.

```mermaid
%%{init: {"flowchart":{"curve":"stepAfter","nodeSpacing":32,"rankSpacing":35}} }%%
flowchart TD
    START[Carrier Row] --> I1{IMS email exact?}
    I1 -- Yes --> IMS_E[Match: IMS Email]
    I1 -- No --> I2{IMS phone exact?}
    I2 -- Yes --> IMS_P[Match: IMS Phone]
    I2 -- No --> I3{IMS first + last exact?}
    I3 -- Yes --> IMS_N[Match: IMS Name]
    I3 -- No --> I4{IMS DOB + last exact?}
    I4 -- Yes --> IMS_D[Match: IMS DOB+Last]
    I4 -- No --> H1{HS email exact?}
    H1 -- Yes --> HS_E[Match: HS Email]
    H1 -- No --> H2{HS phone + effective date ±30d?}
    H2 -- Yes --> HS_P[Match: HS Phone+Date]
    H2 -- No --> U[No deterministic source match]

    IMS_E --> L{Lock List policy/contract found?}
    IMS_P --> L
    IMS_N --> L
    IMS_D --> L
    HS_E --> L
    HS_P --> L
    U --> L

    L -- Yes --> OV[Final result: Lock List override]
    L -- No --> KEEP[Final result: Keep source-stage assignment]
```

## 4) Record Review Lifecycle
This is the analyst-facing state lifecycle once records are in the queue.

```mermaid
stateDiagram-v2
    [*] --> Pending

    Pending --> Matched: Deterministic match produced
    Pending --> Flagged: Analyst flags exception
    Pending --> Resolved: Analyst manually resolves

    Matched --> Resolved: Analyst confirms/updates
    Matched --> Flagged: Analyst escalates issue

    Flagged --> Resolved: Exception closed
    Flagged --> Pending: Returned for re-evaluation

    Resolved --> Archived: Scheduler archival policy
```

## 5) Lock and Resolve Interaction
Shows lock ownership enforcement before a resolve operation is accepted.

```mermaid
sequenceDiagram
    autonumber
    actor Analyst
    participant RC as RecordController
    participant LS as RecordLockService
    participant RQ as reconciliation_queue
    participant AUD as reconciliation_audit_logs

    Analyst->>RC: lock(record)
    RC->>LS: acquire(record, user)
    LS->>RQ: set locked_by, locked_at
    RC->>AUD: write lock_acquired event

    Analyst->>RC: resolve(record, aligned_agent_code, compensation)
    RC->>RQ: validate lock owner + required fields
    RC->>RQ: update status = resolved + aligned values
    RC->>AUD: write resolved event
    RC->>LS: release(record, user)
    LS->>RQ: clear locked_by, locked_at
```

## 6) Contract Patch Processing Flow
Contract patch runs are child runs that update rows under a completed parent standard batch.

```mermaid
sequenceDiagram
    autonumber
    actor Analyst
    participant C as ContractPatchController
    participant S as ContractPatchService
    participant B as import_batches
    participant J as ProcessContractPatchJob
    participant E as ReconciliationETLService
    participant Q as reconciliation_queue
    participant P as contract_patch_logs

    Analyst->>C: Upload contract patch file
    C->>C: Resolve latest completed parent standard batch
    C->>S: startContractPatch(dto)
    S->>B: Create child batch (batch_type = contract_patch)
    S->>J: Dispatch patch job
    C-->>Analyst: 202 Accepted

    J->>E: processContractPatch(batch)
    E->>E: Load parent + historical context

    loop For each contract row
        E->>E: Apply policy checks (flag/history/lock/idempotency)
        E->>Q: Update target queue row(s)
        E->>P: Insert immutable before/after patch log
    end

    E->>B: Update patched/skipped/failed counters
    E->>B: Save output_file_path + final status
```

## 7) Reporting Data Lineage
Use this to explain where each reporting page gets its data.

```mermaid
flowchart LR
    subgraph SOURCES[Primary Data Sources]
        RQ[(reconciliation_queue)]
        CPL[(contract_patch_logs)]
        IB[(import_batches)]
    end

    subgraph REPORTS[Report Surfaces]
        FB[Final BOB]
        LI[Locklist Impact]
        CD[Commission Dashboard]
        PL[Contract Patch Ledger]
    end

    subgraph EXPORTS[Export Implementations]
        EXR[ReconciliationExport]
        EXF[FinalBobExport]
        EXL[LocklistImpactExport]
    end

    RQ --> FB
    RQ --> LI
    RQ --> CD
    CPL --> PL
    IB --> CD
    IB --> PL

    FB --> EXF
    LI --> EXL
    RQ --> EXR
```

## 8) Scheduler and Operations Flow
Operational maintenance runs independently of analyst actions.

```mermaid
flowchart TD
    SCH[Laravel Scheduler] --> L1[release-expired-record-locks]
    SCH --> A1[archive-resolved-records]

    L1 --> L2[RecordLockService.releaseExpired]
    L2 --> RQ[(reconciliation_queue)]

    A1 --> A2[ArchivalService.archiveResolvedRecords]
    A2 --> RQ
```

## 9) Permission Gate to Screen Access
This view maps permission checks to visible functional areas.

```mermaid
flowchart TD
    AUTH[Authenticated User] --> G{Permission / Policy Gate}

    G -- reconciliation.view --> V[Dashboard + Grid + Core Views]
    G -- reconciliation.etl.run --> U[Import + Contract Patch Upload]
    G -- reconciliation.edit --> E[Lock / Resolve / Flag Actions]
    G -- reconciliation.bulk_approve --> B[Bulk Actions + LockList Write + Audit]
    G -- reconciliation.results.view --> R[Reporting Screens]
    G -- reconciliation.export.download --> D[Download / Export Actions]
    G -- access.manage --> A[Access Control Administration]
```
