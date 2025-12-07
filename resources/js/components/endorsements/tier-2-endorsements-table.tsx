import { getPositionIcon, getStatusBadge } from "@/pages/endorsements/trainee";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../ui/table";
import { Endorsement } from "@/types";

export default function Tier2EndorsementsTable({ endorsements }: { endorsements: Endorsement[]}) {
  return (
      <div className="rounded-md border">
          <Table>
              <TableHeader>
                  <TableRow>
                      <TableHead>Position</TableHead>
                      <TableHead>Status</TableHead>
                  </TableRow>
              </TableHeader>
              <TableBody>
                  {endorsements.map((endorsement) => (
                      <TableRow key={endorsement.position} className="h-18">
                          <TableCell>
                              <div className="flex items-center gap-3">
                                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 text-purple-600">
                                      {getPositionIcon(endorsement.type)}
                                  </div>
                                  <div>
                                      <div className="font-medium">{endorsement.position}</div>
                                      <div className="text-sm text-muted-foreground">{endorsement.fullName}</div>
                                  </div>
                              </div>
                          </TableCell>
                          <TableCell>{getStatusBadge(endorsement.status)}</TableCell>
                      </TableRow>
                  ))}
              </TableBody>
          </Table>
      </div>
  );
}