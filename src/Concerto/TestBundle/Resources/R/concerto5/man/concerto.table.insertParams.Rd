\name{concerto.table.insertParams}
\alias{concerto.table.insertParams}
\title{
Table helper function.
}
\description{
Fills SQL code with given parameters.
}
\usage{
concerto.table.insertParams(sql, params = list())
}
\arguments{
  \item{sql}{
SQL code.
}
  \item{params}{
Named list with parameters.
}
}
\details{
}
\value{
SQL code with inserted parameters.
}
\references{
}
\author{
Przemyslaw Lis
}
\note{
}

\seealso{
}
\examples{
id <- 123
sql <- "SELECT * FROM db.table WHERE id={{id_value}}"
sql <- concerto.table.insertParams(sql,list(id_value=id))
}

\keyword{ table }
\keyword{ database }
